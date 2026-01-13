<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // GET /api/admin/dashboard - Statistics
    public function index(Request $request)
    {
        // Total products
        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();

        // Total orders
        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $completedOrders = Order::where('status', 'completed')->count();

        // Revenue (only from completed orders)
        $revenue = Order::where('status', 'completed')
            ->sum('total_amount');

        // Low stock products (stock < 10)
        $lowStockProducts = Product::where('stock', '<', 10)
            ->where('is_active', true)
            ->orderBy('stock', 'asc')
            ->limit(10)
            ->get(['id', 'name', 'stock']);

        // Recent orders
        $recentOrders = Order::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'order_number', 'status', 'total_amount', 'created_at']);

        // Sales by status
        $salesByStatus = Order::select('status', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as total'))
            ->groupBy('status')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard statistics fetched',
            'data' => [
                'products' => [
                    'total' => $totalProducts,
                    'active' => $activeProducts,
                    'low_stock' => $lowStockProducts->count(),
                    'low_stock_items' => $lowStockProducts,
                ],
                'orders' => [
                    'total' => $totalOrders,
                    'pending' => $pendingOrders,
                    'completed' => $completedOrders,
                ],
                'revenue' => [
                    'total' => (float) $revenue,
                    'formatted' => number_format($revenue, 2),
                ],
                'sales_by_status' => $salesByStatus,
                'recent_orders' => $recentOrders,
            ],
        ]);
    }
}

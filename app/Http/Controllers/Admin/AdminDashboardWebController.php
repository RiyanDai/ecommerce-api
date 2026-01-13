<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardWebController extends Controller
{
    public function index()
    {
        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        $totalOrders = Order::count();
        $pendingPaymentOrders = Order::where('payment_status', 'pending')->count();
        $completedOrders = Order::where('order_status', 'completed')->count();
        $revenue = Order::where('order_status', 'completed')
            ->where('payment_status', 'paid')
            ->sum('total_amount');
        $lowStockProducts = Product::where('stock', '<', 10)
            ->where('is_active', true)
            ->orderBy('stock', 'asc')
            ->limit(10)
            ->get();
        $recentOrders = Order::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalProducts',
            'activeProducts',
            'totalOrders',
            'pendingPaymentOrders',
            'completedOrders',
            'revenue',
            'lowStockProducts',
            'recentOrders'
        ));
    }
}

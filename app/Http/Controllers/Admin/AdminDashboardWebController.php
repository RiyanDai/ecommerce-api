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
        $pendingOrders = Order::where('status', 'pending')->count();
        $completedOrders = Order::where('status', 'completed')->count();
        $revenue = Order::where('status', 'completed')->sum('total_amount');
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
            'pendingOrders',
            'completedOrders',
            'revenue',
            'lowStockProducts',
            'recentOrders'
        ));
    }
}

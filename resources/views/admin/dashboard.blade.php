@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Products -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 bg-blue-100 rounded-full">
                <i class="fas fa-box text-blue-600 text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-600 text-sm">Total Products</p>
                <p class="text-2xl font-bold text-gray-800">{{ $totalProducts }}</p>
                <p class="text-xs text-gray-500">{{ $activeProducts }} active</p>
            </div>
        </div>
    </div>

    <!-- Total Orders -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 bg-green-100 rounded-full">
                <i class="fas fa-shopping-cart text-green-600 text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-600 text-sm">Total Orders</p>
                <p class="text-2xl font-bold text-gray-800">{{ $totalOrders }}</p>
                <p class="text-xs text-gray-500">{{ $pendingPaymentOrders }} pending payment</p>
            </div>
        </div>
    </div>

    <!-- Completed Orders -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 bg-purple-100 rounded-full">
                <i class="fas fa-check-circle text-purple-600 text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-600 text-sm">Completed</p>
                <p class="text-2xl font-bold text-gray-800">{{ $completedOrders }}</p>
            </div>
        </div>
    </div>

    <!-- Revenue -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 bg-yellow-100 rounded-full">
                <i class="fas fa-dollar-sign text-yellow-600 text-2xl"></i>
            </div>
            <div class="ml-4">
                <p class="text-gray-600 text-sm">Revenue</p>
                <p class="text-2xl font-bold text-gray-800">Rp {{ number_format($revenue, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Low Stock Products -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Low Stock Products</h3>
        </div>
        <div class="p-6">
            @if($lowStockProducts->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2 px-4 text-sm font-semibold text-gray-700">Product</th>
                                <th class="text-right py-2 px-4 text-sm font-semibold text-gray-700">Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lowStockProducts as $product)
                                <tr class="border-b">
                                    <td class="py-2 px-4">{{ $product->name }}</td>
                                    <td class="py-2 px-4 text-right">
                                        <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-sm">{{ $product->stock }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500 text-center py-4">No low stock products</p>
            @endif
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Recent Orders</h3>
        </div>
        <div class="p-6">
            @if($recentOrders->count() > 0)
                <div class="space-y-4">
                    @foreach($recentOrders as $order)
                        <div class="flex justify-between items-center border-b pb-3">
                            <div>
                                <p class="font-semibold text-gray-800">{{ $order->order_number }}</p>
                                <p class="text-sm text-gray-500">{{ $order->user->name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-800">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</p>
                                <div class="flex flex-col items-end gap-1 mt-1">
                                    <span class="px-2 py-1 rounded text-xs font-semibold
                                        {{ $order->payment_status == 'paid' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $order->payment_status == 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $order->payment_status == 'failed' || $order->payment_status == 'expired' ? 'bg-red-100 text-red-800' : '' }}">
                                        {{ ucfirst($order->payment_status) }}
                                    </span>
                                    <span class="px-2 py-1 rounded text-xs
                                        {{ $order->order_status == 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $order->order_status == 'new' ? 'bg-gray-100 text-gray-800' : '' }}
                                        {{ $order->order_status == 'processing' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $order->order_status == 'shipped' ? 'bg-purple-100 text-purple-800' : '' }}
                                        {{ $order->order_status == 'refunded' ? 'bg-red-100 text-red-800' : '' }}">
                                        {{ ucfirst($order->order_status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-center py-4">No recent orders</p>
            @endif
        </div>
    </div>
</div>
@endsection


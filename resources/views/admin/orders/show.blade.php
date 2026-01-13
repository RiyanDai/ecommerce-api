@extends('layouts.app')

@section('title', 'Order Detail')
@section('page-title', 'Order Detail')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Order Info -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Order Details -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Order Information</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Order Number</p>
                    <p class="font-semibold">{{ $order->order_number }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Payment Status</p>
                    <span class="px-2 py-1 text-xs rounded font-semibold
                        {{ $order->payment_status == 'paid' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $order->payment_status == 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $order->payment_status == 'failed' || $order->payment_status == 'expired' ? 'bg-red-100 text-red-800' : '' }}">
                        {{ ucfirst($order->payment_status) }}
                        @if($order->payment_status == 'paid')
                            <span class="text-xs">(via Midtrans)</span>
                        @endif
                    </span>
                    <p class="text-xs text-gray-400 mt-1">Controlled by Midtrans webhook</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Order Status</p>
                    <span class="px-2 py-1 text-xs rounded
                        {{ $order->order_status == 'completed' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $order->order_status == 'new' ? 'bg-gray-100 text-gray-800' : '' }}
                        {{ $order->order_status == 'processing' ? 'bg-blue-100 text-blue-800' : '' }}
                        {{ $order->order_status == 'shipped' ? 'bg-purple-100 text-purple-800' : '' }}
                        {{ $order->order_status == 'refunded' ? 'bg-red-100 text-red-800' : '' }}">
                        {{ ucfirst($order->order_status) }}
                    </span>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Amount</p>
                    <p class="font-semibold text-lg">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Date</p>
                    <p class="font-semibold">{{ $order->created_at->format('d M Y H:i') }}</p>
                </div>
            </div>
        </div>

        <!-- Customer Info -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Customer Information</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Name</p>
                    <p class="font-semibold">{{ $order->user->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Email</p>
                    <p class="font-semibold">{{ $order->user->email }}</p>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Order Items</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2 px-4 text-sm font-semibold text-gray-700">Product</th>
                            <th class="text-right py-2 px-4 text-sm font-semibold text-gray-700">Quantity</th>
                            <th class="text-right py-2 px-4 text-sm font-semibold text-gray-700">Price</th>
                            <th class="text-right py-2 px-4 text-sm font-semibold text-gray-700">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->orderItems as $item)
                            <tr class="border-b">
                                <td class="py-2 px-4">
                                    <div class="font-medium">{{ $item->product->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $item->product->category->name }}</div>
                                </td>
                                <td class="py-2 px-4 text-right">{{ $item->quantity }}</td>
                                <td class="py-2 px-4 text-right">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                <td class="py-2 px-4 text-right font-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="py-2 px-4 text-right font-semibold">Total</td>
                            <td class="py-2 px-4 text-right font-bold text-lg">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Order Fulfillment Status Update -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6 sticky top-6">
            <h3 class="text-lg font-semibold mb-4">Order Fulfillment</h3>
            
            <!-- Payment Status (Read-Only) -->
            <div class="mb-4 p-3 bg-gray-50 rounded">
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
                <div class="flex items-center justify-between">
                    <span class="px-2 py-1 text-xs rounded font-semibold
                        {{ $order->payment_status == 'paid' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $order->payment_status == 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $order->payment_status == 'failed' || $order->payment_status == 'expired' ? 'bg-red-100 text-red-800' : '' }}">
                        {{ ucfirst($order->payment_status) }}
                    </span>
                    @if($order->payment_status == 'paid')
                        <span class="text-xs text-gray-500">
                            <i class="fas fa-lock"></i> Via Midtrans
                        </span>
                    @endif
                </div>
                <p class="text-xs text-gray-400 mt-1">Controlled by Midtrans webhook only</p>
            </div>

            <!-- Order Status Update Form -->
            <form method="POST" action="{{ route('admin.orders.update-status', $order->id) }}">
                @csrf
                @method('PUT')
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Order Status</label>
                    <select name="order_status" required
                            class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500
                            {{ !$order->isPaymentPaid() && $order->order_status == 'new' ? '' : '' }}"
                            {{ !$order->isPaymentPaid() && $order->order_status != 'new' ? 'disabled' : '' }}>
                        <option value="new" {{ $order->order_status == 'new' ? 'selected' : '' }}>New</option>
                        <option value="processing" {{ $order->order_status == 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="shipped" {{ $order->order_status == 'shipped' ? 'selected' : '' }}>Shipped</option>
                        <option value="completed" {{ $order->order_status == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="refunded" {{ $order->order_status == 'refunded' ? 'selected' : '' }}>Refunded</option>
                    </select>
                    @if(!$order->isPaymentPaid() && $order->order_status != 'new')
                        <p class="text-xs text-red-500 mt-1">
                            <i class="fas fa-exclamation-triangle"></i> Cannot update. Payment must be paid first.
                        </p>
                    @endif
                </div>
                <button type="submit" 
                        class="w-full bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
                        {{ !$order->isPaymentPaid() && $order->order_status != 'new' ? 'disabled' : '' }}>
                    Update Order Status
                </button>
            </form>
            
            @if(!$order->isPaymentPaid())
                <div class="mt-3 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">
                    <i class="fas fa-info-circle"></i> Order fulfillment can only proceed after payment is confirmed via Midtrans.
                </div>
            @endif

            <div class="mt-4 pt-4 border-t">
                <a href="{{ route('admin.orders.index') }}" class="block text-center text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
            </div>
        </div>
    </div>
</div>
@endsection


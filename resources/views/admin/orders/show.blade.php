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
                    <p class="text-sm text-gray-500">Status</p>
                    <span class="px-2 py-1 text-xs rounded 
                        {{ $order->status == 'completed' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $order->status == 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $order->status == 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                        {{ $order->status == 'paid' || $order->status == 'shipped' ? 'bg-blue-100 text-blue-800' : '' }}">
                        {{ ucfirst($order->status) }}
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

    <!-- Status Update -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6 sticky top-6">
            <h3 class="text-lg font-semibold mb-4">Update Status</h3>
            <form method="POST" action="{{ route('admin.orders.update-status', $order->id) }}">
                @csrf
                @method('PUT')
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" required
                            class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="paid" {{ $order->status == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="shipped" {{ $order->status == 'shipped' ? 'selected' : '' }}>Shipped</option>
                        <option value="completed" {{ $order->status == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <button type="submit" class="w-full bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Update Status
                </button>
            </form>
            <div class="mt-4 pt-4 border-t">
                <a href="{{ route('admin.orders.index') }}" class="block text-center text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
            </div>
        </div>
    </div>
</div>
@endsection


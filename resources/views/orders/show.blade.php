@extends('layouts.customer')

@section('title', 'Order Details')

@section('content')
<div class="mb-6">
    <a href="{{ route('orders.index') }}" class="text-blue-600 hover:text-blue-800">
        <i class="fas fa-arrow-left mr-2"></i>Back to Orders
    </a>
</div>

<h1 class="text-3xl font-bold mb-6">Order Details</h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Order Information -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Order Info Card -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold mb-4">Order Information</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Order Number</p>
                    <p class="font-semibold text-lg">{{ $order->order_number }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Order Date</p>
                    <p class="font-semibold">{{ $order->created_at->format('d M Y, H:i') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <span class="px-3 py-1 rounded-full text-sm font-semibold
                        {{ $order->status == 'completed' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $order->status == 'pending' ? 'bg-orange-100 text-orange-800' : '' }}
                        {{ $order->status == 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                        {{ in_array($order->status, ['paid', 'shipped']) ? 'bg-blue-100 text-blue-800' : '' }}">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Amount</p>
                    <p class="font-semibold text-lg text-blue-600">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold mb-4">Order Items</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Product</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Quantity</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Price</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($order->orderItems as $item)
                            <tr>
                                <td class="px-4 py-4">
                                    <div class="flex items-center">
                                        @if($item->product->image)
                                            <img src="{{ asset('storage/' . $item->product->image) }}" 
                                                 alt="{{ $item->product->name }}" 
                                                 class="w-12 h-12 object-cover rounded mr-3">
                                        @else
                                            <div class="w-12 h-12 bg-gray-200 rounded mr-3 flex items-center justify-center">
                                                <i class="fas fa-image text-gray-400 text-xs"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <p class="font-semibold">{{ $item->product->name }}</p>
                                            <p class="text-sm text-gray-500">{{ $item->product->category->name }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center">{{ $item->quantity }}</td>
                                <td class="px-4 py-4 text-right">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                                <td class="px-4 py-4 text-right font-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="3" class="px-4 py-3 text-right font-semibold">Total</td>
                            <td class="px-4 py-3 text-right font-bold text-lg">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Cancel Order (if pending) -->
        @if($order->status == 'pending')
            <div class="bg-white rounded-lg shadow-md p-6">
                <form action="{{ route('orders.cancel', $order->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?')">
                    @csrf
                    <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-times mr-2"></i>Cancel Order
                    </button>
                </form>
            </div>
        @endif
    </div>

    <!-- Payment Summary -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
            <h2 class="text-xl font-bold mb-4">Payment Summary</h2>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">Subtotal</span>
                    <span class="font-semibold">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Shipping</span>
                    <span class="font-semibold">Free</span>
                </div>
                <div class="border-t pt-2 mt-2">
                    <div class="flex justify-between text-lg font-bold">
                        <span>Total</span>
                        <span class="text-blue-600">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@extends('layouts.customer')

@section('title', 'Order Success')

@section('content')
<div class="max-w-2xl mx-auto text-center py-12">
    <!-- Success Icon -->
    <div class="mb-6">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto">
            <i class="fas fa-check-circle text-green-600 text-5xl"></i>
        </div>
    </div>

    <h1 class="text-4xl font-bold text-gray-800 mb-4">Order Placed Successfully!</h1>
    
    <!-- Order Number -->
    <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-6 mb-6">
        <p class="text-sm text-gray-600 mb-2">Order Number</p>
        <p class="text-3xl font-bold text-blue-600">{{ $order->order_number }}</p>
    </div>

    <!-- Order Summary -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8 text-left">
        <h2 class="text-xl font-bold mb-4">Order Summary</h2>
        <div class="space-y-2">
            <div class="flex justify-between">
                <span class="text-gray-600">Order Date</span>
                <span class="font-semibold">{{ $order->created_at->format('d M Y, H:i') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Items</span>
                <span class="font-semibold">{{ $order->orderItems->count() }} items</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Total Amount</span>
                <span class="font-semibold text-lg">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between pt-2 border-t">
                <span class="text-gray-600">Status</span>
                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-semibold">
                    {{ ucfirst($order->status) }}
                </span>
            </div>
        </div>
    </div>

    <!-- Message -->
    <p class="text-gray-600 mb-8">
        We'll process your order soon. Check 'My Orders' for updates.
    </p>

    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="{{ route('orders.show', $order->id) }}" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-semibold">
            <i class="fas fa-box mr-2"></i>View My Orders
        </a>
        <a href="{{ route('home') }}" class="bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 transition-colors font-semibold">
            <i class="fas fa-shopping-bag mr-2"></i>Continue Shopping
        </a>
    </div>
</div>
@endsection


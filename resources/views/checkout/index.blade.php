@extends('layouts.customer')

@section('title', 'Checkout')

@section('content')
<h1 class="text-3xl font-bold mb-6">Checkout</h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Order Summary -->
    <div class="lg:col-span-1 order-2 lg:order-1">
        <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
            <h2 class="text-xl font-bold mb-4">Order Summary</h2>
            <div class="space-y-3 mb-4">
                @foreach($carts as $cart)
                    <div class="flex items-center space-x-3 pb-3 border-b">
                        @if($cart->product->image)
                            <img src="{{ asset('storage/' . $cart->product->image) }}" 
                                 alt="{{ $cart->product->name }}" 
                                 class="w-12 h-12 object-cover rounded">
                        @else
                            <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                <i class="fas fa-image text-gray-400 text-xs"></i>
                            </div>
                        @endif
                        <div class="flex-1">
                            <p class="text-sm font-semibold">{{ $cart->product->name }}</p>
                            <p class="text-xs text-gray-500">Qty: {{ $cart->quantity }} × Rp {{ number_format($cart->product->price, 0, ',', '.') }}</p>
                        </div>
                        <span class="text-sm font-semibold">Rp {{ number_format($cart->quantity * $cart->product->price, 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
            <div class="border-t pt-4 space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">Subtotal</span>
                    <span class="font-semibold">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Shipping</span>
                    <span class="font-semibold">Free</span>
                </div>
                <div class="flex justify-between text-lg font-bold pt-2 border-t">
                    <span>Total</span>
                    <span class="text-blue-600">Rp {{ number_format($total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Shipping Form -->
    <div class="lg:col-span-2 order-1 lg:order-2">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold mb-6">Shipping Information</h2>
            <form action="{{ route('checkout.process') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                        <input type="text" name="customer_name" value="{{ old('customer_name', auth()->user()->name) }}" required
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('customer_name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                        <input type="email" name="customer_email" value="{{ old('customer_email', auth()->user()->email) }}" required
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('customer_email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                        <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" required
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('customer_phone')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Shipping Address *</label>
                        <textarea name="shipping_address" rows="4" required
                                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('shipping_address') }}</textarea>
                        @error('shipping_address')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                        <textarea name="notes" rows="3"
                                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-semibold text-lg">
                        <i class="fas fa-check mr-2"></i>Place Order
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


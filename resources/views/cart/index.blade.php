@extends('layouts.customer')

@section('title', 'Shopping Cart')

@section('content')
<h1 class="text-3xl font-bold mb-6">Shopping Cart</h1>

@if($carts->count() > 0)
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Cart Items -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Product</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Price</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Quantity</th>
                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Subtotal</th>
                            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($carts as $cart)
                            <tr>
                                <td class="px-4 py-4">
                                    <div class="flex items-center">
                                        @if($cart->product->image)
                                            <img src="{{ asset('storage/' . $cart->product->image) }}" 
                                                 alt="{{ $cart->product->name }}" 
                                                 class="w-16 h-16 object-cover rounded mr-4">
                                        @else
                                            <div class="w-16 h-16 bg-gray-200 rounded mr-4 flex items-center justify-center">
                                                <i class="fas fa-image text-gray-400"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <a href="{{ route('products.show', $cart->product->slug) }}" class="font-semibold text-gray-800 hover:text-blue-600">
                                                {{ $cart->product->name }}
                                            </a>
                                            <p class="text-sm text-gray-500">{{ $cart->product->category->name }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="text-gray-800">Rp {{ number_format($cart->product->price, 0, ',', '.') }}</span>
                                </td>
                                <td class="px-4 py-4">
                                    <form action="{{ route('cart.update', $cart->id) }}" method="POST" class="flex items-center justify-center">
                                        @csrf
                                        @method('PUT')
                                        <div class="flex items-center border rounded">
                                            <button type="button" onclick="decreaseQty({{ $cart->id }})" class="px-2 py-1 hover:bg-gray-100">
                                                <i class="fas fa-minus text-xs"></i>
                                            </button>
                                            <input type="number" name="quantity" id="qty-{{ $cart->id }}" value="{{ $cart->quantity }}" 
                                                   min="1" max="{{ $cart->product->stock }}" 
                                                   class="w-12 text-center border-0 focus:outline-none" onchange="this.form.submit()">
                                            <button type="button" onclick="increaseQty({{ $cart->id }}, {{ $cart->product->stock }})" class="px-2 py-1 hover:bg-gray-100">
                                                <i class="fas fa-plus text-xs"></i>
                                            </button>
                                        </div>
                                    </form>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <span class="font-semibold text-gray-800">Rp {{ number_format($cart->quantity * $cart->product->price, 0, ',', '.') }}</span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <form action="{{ route('cart.remove', $cart->id) }}" method="POST" onsubmit="return confirm('Remove this item from cart?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <a href="{{ route('home') }}" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i>Continue Shopping
                </a>
            </div>
        </div>

        <!-- Cart Summary -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
                <h2 class="text-xl font-bold mb-4">Order Summary</h2>
                <div class="space-y-3 mb-4">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-semibold">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Shipping</span>
                        <span class="font-semibold">Free</span>
                    </div>
                    <div class="border-t pt-3">
                        <div class="flex justify-between">
                            <span class="text-lg font-bold">Total</span>
                            <span class="text-lg font-bold text-blue-600">Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
                <a href="{{ route('checkout.index') }}" class="block w-full bg-blue-600 text-white text-center py-3 rounded-lg hover:bg-blue-700 transition-colors font-semibold">
                    Proceed to Checkout
                </a>
            </div>
        </div>
    </div>
@else
    <!-- Empty Cart -->
    <div class="text-center py-12 bg-white rounded-lg shadow-md">
        <i class="fas fa-shopping-cart text-6xl text-gray-400 mb-4"></i>
        <h3 class="text-2xl font-semibold text-gray-700 mb-2">Your cart is empty</h3>
        <p class="text-gray-500 mb-6">Add some products to your cart to get started!</p>
        <a href="{{ route('home') }}" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
            Start Shopping
        </a>
    </div>
@endif

@section('scripts')
<script>
function decreaseQty(cartId) {
    const input = document.getElementById('qty-' + cartId);
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
        input.form.submit();
    }
}

function increaseQty(cartId, maxStock) {
    const input = document.getElementById('qty-' + cartId);
    if (parseInt(input.value) < maxStock) {
        input.value = parseInt(input.value) + 1;
        input.form.submit();
    }
}
</script>
@endsection
@endsection


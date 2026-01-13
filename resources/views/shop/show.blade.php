@extends('layouts.customer')

@section('title', $product->name)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
    <!-- Product Image -->
    <div>
        @if($product->image)
            <img src="{{ asset('storage/' . $product->image) }}" 
                 alt="{{ $product->name }}" 
                 class="w-full rounded-lg shadow-lg">
        @else
            <div class="w-full h-96 bg-gray-200 rounded-lg flex items-center justify-center">
                <i class="fas fa-image text-gray-400 text-6xl"></i>
            </div>
        @endif
    </div>

    <!-- Product Info -->
    <div>
        <div class="mb-4">
            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                {{ $product->category->name }}
            </span>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-4">{{ $product->name }}</h1>
        <div class="mb-6">
            <span class="text-4xl font-bold text-blue-600">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
        </div>

        <!-- Stock Status -->
        <div class="mb-6">
            @if($product->stock > 0)
                <p class="text-green-600 font-semibold">
                    <i class="fas fa-check-circle"></i> In Stock: {{ $product->stock }} items
                </p>
            @else
                <p class="text-red-600 font-semibold">
                    <i class="fas fa-times-circle"></i> Out of Stock
                </p>
            @endif
        </div>

        <!-- Add to Cart Form -->
        <form action="{{ route('cart.add') }}" method="POST" class="mb-6">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            
            <div class="flex items-center space-x-4 mb-4">
                <label class="font-semibold">Quantity:</label>
                <div class="flex items-center border rounded">
                    <button type="button" onclick="decreaseQty()" class="px-3 py-2 hover:bg-gray-100" id="decreaseBtn">
                        <i class="fas fa-minus"></i>
                    </button>
                    <input type="number" name="quantity" id="quantity" value="1" min="1" max="{{ $product->stock }}" 
                           class="w-16 text-center border-0 focus:outline-none" readonly>
                    <button type="button" onclick="increaseQty()" class="px-3 py-2 hover:bg-gray-100" id="increaseBtn">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>

            <button type="submit" 
                    class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-semibold text-lg {{ $product->stock == 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                    {{ $product->stock == 0 ? 'disabled' : '' }}>
                <i class="fas fa-cart-plus mr-2"></i>Add to Cart
            </button>
        </form>

        <!-- Description -->
        <div class="border-t pt-6">
            <h3 class="font-semibold text-lg mb-2">Description</h3>
            <p class="text-gray-600">{{ $product->description ?? 'No description available.' }}</p>
        </div>
    </div>
</div>

<!-- Related Products -->
@if($relatedProducts->count() > 0)
    <div class="mt-12">
        <h2 class="text-2xl font-bold mb-6">Related Products</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($relatedProducts as $relatedProduct)
                <x-product-card :product="$relatedProduct" />
            @endforeach
        </div>
    </div>
@endif

@section('scripts')
<script>
const maxStock = {{ $product->stock }};
const quantityInput = document.getElementById('quantity');
const decreaseBtn = document.getElementById('decreaseBtn');
const increaseBtn = document.getElementById('increaseBtn');

function decreaseQty() {
    let qty = parseInt(quantityInput.value);
    if (qty > 1) {
        qty--;
        quantityInput.value = qty;
    }
}

function increaseQty() {
    let qty = parseInt(quantityInput.value);
    if (qty < maxStock) {
        qty++;
        quantityInput.value = qty;
    }
}

// Disable buttons if needed
if (maxStock === 0) {
    decreaseBtn.disabled = true;
    increaseBtn.disabled = true;
}
</script>
@endsection
@endsection


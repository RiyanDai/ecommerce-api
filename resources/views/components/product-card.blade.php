@props(['product'])

<div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
    <a href="{{ route('products.show', $product->slug) }}">
        <div class="relative">
            @if($product->image)
                <img src="{{ asset('storage/' . $product->image) }}" 
                     alt="{{ $product->name }}" 
                     class="w-full h-48 object-cover">
            @else
                <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                    <i class="fas fa-image text-gray-400 text-4xl"></i>
                </div>
            @endif
            @if($product->stock < 10 && $product->stock > 0)
                <span class="absolute top-2 right-2 bg-yellow-500 text-white text-xs px-2 py-1 rounded">Low Stock</span>
            @elseif($product->stock == 0)
                <span class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded">Out of Stock</span>
            @endif
        </div>
    </a>
    <div class="p-4">
        <a href="{{ route('products.show', $product->slug) }}">
            <h3 class="font-semibold text-lg text-gray-800 mb-2 hover:text-blue-600">{{ $product->name }}</h3>
        </a>
        <p class="text-sm text-gray-500 mb-2">{{ $product->category->name }}</p>
        <div class="flex items-center justify-between mb-3">
            <span class="text-2xl font-bold text-blue-600">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
            <span class="text-sm text-gray-500">Stock: {{ $product->stock }}</span>
        </div>
        <form action="{{ route('cart.add') }}" method="POST" class="mt-2">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="quantity" value="1">
            <button type="submit" 
                    class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition-colors {{ $product->stock == 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                    {{ $product->stock == 0 ? 'disabled' : '' }}>
                <i class="fas fa-cart-plus mr-2"></i>Add to Cart
            </button>
        </form>
    </div>
</div>


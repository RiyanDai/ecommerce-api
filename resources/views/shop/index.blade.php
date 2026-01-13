@extends('layouts.customer')

@section('title', 'Shop')

@section('content')
<!-- Hero Section -->
<div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg p-8 mb-8 text-center">
    <h1 class="text-4xl font-bold mb-4">Welcome to E-Commerce</h1>
    <p class="text-xl">Discover amazing products at great prices</p>
</div>

<!-- Categories Filter -->
<div class="mb-8">
    <h2 class="text-xl font-semibold mb-4">Categories</h2>
    <div class="flex space-x-2 overflow-x-auto pb-2">
        <a href="{{ route('home') }}" 
           class="px-4 py-2 rounded-full {{ !request('category_id') ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }} whitespace-nowrap">
            All
        </a>
        @foreach($categories as $category)
            <a href="{{ route('home', ['category_id' => $category->id]) }}" 
               class="px-4 py-2 rounded-full {{ request('category_id') == $category->id ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }} whitespace-nowrap">
                {{ $category->name }}
            </a>
        @endforeach
    </div>
</div>

<!-- Products Grid -->
@if($products->count() > 0)
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
        @foreach($products as $product)
            <x-product-card :product="$product" />
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $products->links() }}
    </div>
@else
    <!-- Empty State -->
    <div class="text-center py-12">
        <i class="fas fa-box-open text-6xl text-gray-400 mb-4"></i>
        <h3 class="text-2xl font-semibold text-gray-700 mb-2">No Products Found</h3>
        <p class="text-gray-500 mb-4">Try adjusting your search or filter criteria.</p>
        <a href="{{ route('home') }}" class="inline-block bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
            View All Products
        </a>
    </div>
@endif
@endsection


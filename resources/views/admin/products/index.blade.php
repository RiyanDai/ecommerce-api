@extends('layouts.app')

@section('title', 'Products')
@section('page-title', 'Products')



@section('content')
<div class="w-full">
<div class="mb-6 flex justify-between items-center">
    <div class="flex items-center space-x-4">
        <form method="GET" action="{{ route('admin.products.index') }}" class="flex items-center space-x-2">
            <input type="text" name="search" value="{{ request('search') }}" 
                   placeholder="Search products..." 
                   class="border rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <select name="category_id" class="border rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                <i class="fas fa-search"></i> Search
            </button>
            @if(request('search') || request('category_id'))
                <a href="{{ route('admin.products.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    Clear
                </a>
            @endif
        </form>
    </div>
    <a href="{{ route('admin.products.create') }}" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
        <i class="fas fa-plus"></i> Add Product
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($products as $product)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" 
                                 alt="{{ $product->name }}" 
                                 class="w-16 h-16 object-cover rounded border"
                                 onerror="console.error('Image not found: {{ asset('storage/' . $product->image) }}'); this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="w-16 h-16 bg-gray-200 rounded flex items-center justify-center border hidden">
                                <i class="fas fa-image text-gray-400"></i>
                            </div>
                        @else
                            <div class="w-16 h-16 bg-gray-200 rounded flex items-center justify-center border">
                                <i class="fas fa-image text-gray-400"></i>
                            </div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $product->category->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded {{ $product->stock < 10 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                            {{ $product->stock }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $product->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
                        <a href="{{ route('admin.products.edit', $product->id) }}" class="text-blue-600 hover:text-blue-900">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button onclick="openAddStockModal({{ $product->id }}, '{{ $product->name }}')" class="text-green-600 hover:text-green-900">
                            <i class="fas fa-plus-circle"></i>
                        </button>
                        <button onclick="confirmDelete({{ $product->id }})" class="text-red-600 hover:text-red-900">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No products found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $products->links() }}
</div>
@endsection

<!-- Add Stock Modal -->
<div id="addStockModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-bold mb-4">Add Stock</h3>
        <p class="text-sm text-gray-600 mb-4" id="productName"></p>
        <form id="addStockForm" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                <input type="number" name="quantity" min="1" required 
                       class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description (optional)</label>
                <input type="text" name="description" 
                       class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeAddStockModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Add Stock</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-bold mb-4">Confirm Delete</h3>
        <p class="text-sm text-gray-600 mb-4">Are you sure you want to delete this product? This action cannot be undone.</p>
        <form id="deleteForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Delete</button>
            </div>
        </form>
    </div>
</div>





@section('scripts')
<script>
function openAddStockModal(productId, productName) {
    document.getElementById('productName').textContent = 'Product: ' + productName;
    document.getElementById('addStockForm').action = '/admin/products/' + productId + '/add-stock';
    document.getElementById('addStockModal').classList.remove('hidden');
}

function closeAddStockModal() {
    document.getElementById('addStockModal').classList.add('hidden');
}

function confirmDelete(productId) {
    document.getElementById('deleteForm').action = '/admin/products/' + productId;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}
</script>
</div>
@endsection

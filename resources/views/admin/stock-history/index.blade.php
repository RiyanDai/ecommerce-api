@extends('layouts.app')

@section('title', 'Stock History')
@section('page-title', 'Stock History')

@section('content')
<div class="mb-6">
    <form method="GET" action="{{ route('admin.stock-history.index') }}" class="flex items-center space-x-2">
        <select name="type" class="border rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All Types</option>
            <option value="in" {{ request('type') == 'in' ? 'selected' : '' }}>Stock In</option>
            <option value="out" {{ request('type') == 'out' ? 'selected' : '' }}>Stock Out</option>
            <option value="adjustment" {{ request('type') == 'adjustment' ? 'selected' : '' }}>Adjustment</option>
        </select>
        <input type="number" name="product_id" value="{{ request('product_id') }}" 
               placeholder="Product ID" 
               class="border rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <input type="number" name="order_id" value="{{ request('order_id') }}" 
               placeholder="Order ID" 
               class="border rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            <i class="fas fa-filter"></i> Filter
        </button>
        @if(request('type') || request('product_id') || request('order_id'))
            <a href="{{ route('admin.stock-history.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                Clear
            </a>
        @endif
    </form>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Change</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock Before</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock After</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($histories as $history)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $history->created_at->format('d M Y H:i') }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $history->product->name }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded 
                            {{ $history->type == 'in' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $history->type == 'out' ? 'bg-red-100 text-red-800' : '' }}
                            {{ $history->type == 'adjustment' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                            {{ ucfirst($history->type) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm font-semibold 
                        {{ $history->change > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $history->change > 0 ? '+' : '' }}{{ $history->change }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $history->stock_before }}</td>
                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ $history->stock_after }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $history->user->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $history->description }}
                        @if($history->order_id)
                            <br><span class="text-xs text-blue-600">Order #{{ $history->order->order_number ?? $history->order_id }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">No stock history found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $histories->links() }}
</div>
@endsection


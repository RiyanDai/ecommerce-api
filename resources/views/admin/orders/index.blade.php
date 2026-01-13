@extends('layouts.app')

@section('title', 'Orders')
@section('page-title', 'Orders')

@section('content')
<div class="mb-6">
    <form method="GET" action="{{ route('admin.orders.index') }}" class="flex items-center space-x-2">
        <select name="status" class="border rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All Status</option>
            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
            <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>Shipped</option>
            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
        </select>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            <i class="fas fa-filter"></i> Filter
        </button>
        @if(request('status'))
            <a href="{{ route('admin.orders.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                Clear
            </a>
        @endif
    </form>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order Number</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Amount</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($orders as $order)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $order->order_number }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ $order->user->name }}</div>
                        <div class="text-sm text-gray-500">{{ $order->user->email }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded 
                            {{ $order->status == 'completed' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $order->status == 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $order->status == 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                            {{ $order->status == 'paid' || $order->status == 'shipped' ? 'bg-blue-100 text-blue-800' : '' }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $order->created_at->format('d M Y H:i') }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <a href="{{ route('admin.orders.show', $order->id) }}" class="text-blue-600 hover:text-blue-900">
                            <i class="fas fa-eye"></i> View
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No orders found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $orders->links() }}
</div>
@endsection


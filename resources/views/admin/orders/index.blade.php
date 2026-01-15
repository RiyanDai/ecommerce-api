 @extends('layouts.customer')

@section('title', 'Orders')
 

@section('content')
<div class="mb-6">
    <form method="GET" action="{{ route('admin.orders.index') }}" class="flex items-center space-x-2">
        <select name="payment_status" class="border rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All Payment Status</option>
            <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
            <option value="failed" {{ request('payment_status') == 'failed' ? 'selected' : '' }}>Failed</option>
            <option value="expired" {{ request('payment_status') == 'expired' ? 'selected' : '' }}>Expired</option>
        </select>
        <select name="order_status" class="border rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All Order Status</option>
            <option value="new" {{ request('order_status') == 'new' ? 'selected' : '' }}>New</option>
            <option value="processing" {{ request('order_status') == 'processing' ? 'selected' : '' }}>Processing</option>
            <option value="shipped" {{ request('order_status') == 'shipped' ? 'selected' : '' }}>Shipped</option>
            <option value="completed" {{ request('order_status') == 'completed' ? 'selected' : '' }}>Completed</option>
            <option value="refunded" {{ request('order_status') == 'refunded' ? 'selected' : '' }}>Refunded</option>
        </select>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            <i class="fas fa-filter"></i> Filter
        </button>
        @if(request('payment_status') || request('order_status'))
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
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order Status</th>
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
                        <span class="px-2 py-1 text-xs rounded font-semibold
                            {{ $order->payment_status == 'paid' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $order->payment_status == 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $order->payment_status == 'failed' || $order->payment_status == 'expired' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ ucfirst($order->payment_status) }}
                        </span>
                        @if($order->payment_status == 'paid')
                            <div class="text-xs text-gray-400 mt-1">
                                <i class="fas fa-lock"></i> Midtrans
                            </div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded
                            {{ $order->order_status == 'completed' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $order->order_status == 'new' ? 'bg-gray-100 text-gray-800' : '' }}
                            {{ $order->order_status == 'processing' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $order->order_status == 'shipped' ? 'bg-purple-100 text-purple-800' : '' }}
                            {{ $order->order_status == 'refunded' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ ucfirst($order->order_status) }}
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
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No orders found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
<div class="mt-4">
    {{ $orders->links() }}
</div>



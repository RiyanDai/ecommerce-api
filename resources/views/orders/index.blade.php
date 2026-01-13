@extends('layouts.customer')

@section('title', 'My Orders')

@section('content')
<h1 class="text-3xl font-bold mb-6">My Orders</h1>

<!-- Filter Tabs -->
<div class="mb-6 flex space-x-2 overflow-x-auto pb-2">
    <a href="{{ route('orders.index') }}" 
       class="px-4 py-2 rounded-full whitespace-nowrap {{ !request('status') ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
        All
    </a>
    <a href="{{ route('orders.index', ['status' => 'pending']) }}" 
       class="px-4 py-2 rounded-full whitespace-nowrap {{ request('status') == 'pending' ? 'bg-orange-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
        Pending
    </a>
    <a href="{{ route('orders.index', ['status' => 'paid']) }}" 
       class="px-4 py-2 rounded-full whitespace-nowrap {{ request('status') == 'paid' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
        Processing
    </a>
    <a href="{{ route('orders.index', ['status' => 'completed']) }}" 
       class="px-4 py-2 rounded-full whitespace-nowrap {{ request('status') == 'completed' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
        Completed
    </a>
    <a href="{{ route('orders.index', ['status' => 'cancelled']) }}" 
       class="px-4 py-2 rounded-full whitespace-nowrap {{ request('status') == 'cancelled' ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
        Cancelled
    </a>
</div>

@if($orders->count() > 0)
    <div class="space-y-4">
        @foreach($orders as $order)
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div class="mb-4 md:mb-0">
                        <div class="flex items-center space-x-4 mb-2">
                            <h3 class="text-lg font-semibold text-gray-800">{{ $order->order_number }}</h3>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                {{ $order->status == 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $order->status == 'pending' ? 'bg-orange-100 text-orange-800' : '' }}
                                {{ $order->status == 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                                {{ in_array($order->status, ['paid', 'shipped']) ? 'bg-blue-100 text-blue-800' : '' }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500">
                            <i class="far fa-calendar mr-1"></i>{{ $order->created_at->format('d M Y, H:i') }}
                        </p>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ $order->orderItems->count() }} items • Total: <span class="font-semibold">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                        </p>
                    </div>
                    <div>
                        <a href="{{ route('orders.show', $order->id) }}" 
                           class="inline-block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-eye mr-2"></i>View Details
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $orders->links() }}
    </div>
@else
    <!-- Empty State -->
    <div class="text-center py-12 bg-white rounded-lg shadow-md">
        <i class="fas fa-box-open text-6xl text-gray-400 mb-4"></i>
        <h3 class="text-2xl font-semibold text-gray-700 mb-2">No orders yet</h3>
        <p class="text-gray-500 mb-6">Start shopping to see your orders here!</p>
        <a href="{{ route('home') }}" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
            Start Shopping
        </a>
    </div>
@endif
@endsection


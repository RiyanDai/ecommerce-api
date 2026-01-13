@extends('layouts.customer')

@section('title', 'My Orders')

@section('content')
<h1 class="text-3xl font-bold mb-6">My Orders</h1>

<!-- Filter Tabs -->
<div class="mb-6 flex space-x-2 overflow-x-auto pb-2">
    <a href="{{ route('orders.index') }}" 
       class="px-4 py-2 rounded-full whitespace-nowrap {{ !request('order_status') && !request('payment_status') ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
        All
    </a>
    <a href="{{ route('orders.index', ['payment_status' => 'pending']) }}" 
       class="px-4 py-2 rounded-full whitespace-nowrap {{ request('payment_status') == 'pending' ? 'bg-orange-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
        Pending Payment
    </a>
    <a href="{{ route('orders.index', ['payment_status' => 'paid', 'order_status' => 'new']) }}" 
       class="px-4 py-2 rounded-full whitespace-nowrap {{ request('payment_status') == 'paid' && request('order_status') == 'new' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
        Processing
    </a>
    <a href="{{ route('orders.index', ['order_status' => 'completed']) }}" 
       class="px-4 py-2 rounded-full whitespace-nowrap {{ request('order_status') == 'completed' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
        Completed
    </a>
    <a href="{{ route('orders.index', ['payment_status' => 'failed']) }}" 
       class="px-4 py-2 rounded-full whitespace-nowrap {{ request('payment_status') == 'failed' ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
        Failed/Cancelled
    </a>
</div>

@if($orders->count() > 0)
    <div class="space-y-4">
        @foreach($orders as $order)
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div class="mb-4 md:mb-0">
                        <div class="flex items-center space-x-4 mb-2 flex-wrap">
                            <h3 class="text-lg font-semibold text-gray-800">{{ $order->order_number }}</h3>
                            <!-- Payment Status Badge -->
                            <div class="flex items-center space-x-2">
                                <span id="paymentStatusBadge-{{ $order->id }}" class="px-3 py-1 rounded-full text-xs font-semibold
                                    {{ $order->payment_status == 'paid' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $order->payment_status == 'pending' ? 'bg-orange-100 text-orange-800' : '' }}
                                    {{ $order->payment_status == 'failed' || $order->payment_status == 'expired' ? 'bg-red-100 text-red-800' : '' }}">
                                    Payment: {{ ucfirst($order->payment_status) }}
                                </span>
                                @if($order->payment_status == 'pending')
                                    <button onclick="checkPaymentStatus('{{ $order->order_number }}', {{ $order->id }})"
                                            class="px-2 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                @endif
                            </div>
                            <!-- Order Status Badge -->
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                {{ $order->order_status == 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $order->order_status == 'new' ? 'bg-gray-100 text-gray-800' : '' }}
                                {{ $order->order_status == 'processing' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $order->order_status == 'shipped' ? 'bg-purple-100 text-purple-800' : '' }}
                                {{ $order->order_status == 'refunded' ? 'bg-red-100 text-red-800' : '' }}">
                                Order: {{ ucfirst($order->order_status) }}
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

@section('scripts')
<script>
function checkPaymentStatus(orderNumber, orderId) {
    const badge = document.getElementById('paymentStatusBadge-' + orderId);
    const btn = event.target;
    
    if (!badge) {
        console.error('Payment status badge not found');
        return;
    }
    
    // Show loading state
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || 
                      document.querySelector('input[name="_token"]')?.value;
    
    fetch('{{ route('payment.check-status') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
            order_number: orderNumber
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const status = data.payment_status.toLowerCase();
            badge.textContent = 'Payment: ' + (status.charAt(0).toUpperCase() + status.slice(1));
            
            // Update badge colors
            badge.className = 'px-3 py-1 rounded-full text-xs font-semibold ';
            if (status === 'paid') {
                badge.className += 'bg-green-100 text-green-800';
                btn.style.display = 'none';
                // Reload page to show updated status
                setTimeout(() => window.location.reload(), 1500);
            } else if (status === 'pending') {
                badge.className += 'bg-orange-100 text-orange-800';
            } else if (status === 'failed' || status === 'expired') {
                badge.className += 'bg-red-100 text-red-800';
                btn.style.display = 'none';
            }
            
            if (data.status_updated) {
                setTimeout(() => window.location.reload(), 2000);
            }
        } else {
            alert('Error: ' + (data.message || 'Failed to check status'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error checking payment status. Please try again.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}
</script>
@endsection


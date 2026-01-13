@extends('layouts.customer')

@section('title', 'Checkout')

@section('content')
<h1 class="text-3xl font-bold mb-6">Checkout</h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Order Summary -->
    <div class="lg:col-span-1 order-2 lg:order-1">
        <div class="bg-white rounded-lg shadow-md p-6 sticky top-24">
            <h2 class="text-xl font-bold mb-4">Order Summary</h2>
            <div class="space-y-3 mb-4">
                @foreach($carts as $cart)
                    <div class="flex items-center space-x-3 pb-3 border-b">
                        @if($cart->product->image)
                            <img src="{{ asset('storage/' . $cart->product->image) }}" 
                                 alt="{{ $cart->product->name }}" 
                                 class="w-12 h-12 object-cover rounded">
                        @else
                            <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                <i class="fas fa-image text-gray-400 text-xs"></i>
                            </div>
                        @endif
                        <div class="flex-1">
                            <p class="text-sm font-semibold">{{ $cart->product->name }}</p>
                            <p class="text-xs text-gray-500">Qty: {{ $cart->quantity }} × Rp {{ number_format($cart->product->price, 0, ',', '.') }}</p>
                        </div>
                        <span class="text-sm font-semibold">Rp {{ number_format($cart->quantity * $cart->product->price, 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
            <div class="border-t pt-4 space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-600">Subtotal</span>
                    <span class="font-semibold">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Shipping</span>
                    <span class="font-semibold">Free</span>
                </div>
                <div class="flex justify-between text-lg font-bold pt-2 border-t">
                    <span>Total</span>
                    <span class="text-blue-600">Rp {{ number_format($total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Shipping Form -->
    <div class="lg:col-span-2 order-1 lg:order-2">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold mb-6">Shipping Information</h2>
            <form action="{{ route('checkout.process') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                        <input type="text" name="customer_name" value="{{ old('customer_name', auth()->user()->name) }}" required
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('customer_name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                        <input type="email" name="customer_email" value="{{ old('customer_email', auth()->user()->email) }}" required
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('customer_email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                        <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" required
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('customer_phone')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Shipping Address *</label>
                        <textarea name="shipping_address" rows="4" required
                                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('shipping_address') }}</textarea>
                        @error('shipping_address')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                        <textarea name="notes" rows="3"
                                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" id="placeOrderBtn" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-semibold text-lg disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-check mr-2"></i><span id="btnText">Place Order</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Midtrans Snap.js -->
<script type="text/javascript" src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('midtrans.client_key') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkoutForm = document.querySelector('form[action="{{ route('checkout.process') }}"]');
    const placeOrderBtn = document.getElementById('placeOrderBtn');
    const btnText = document.getElementById('btnText');

    if (!checkoutForm || !placeOrderBtn) {
        console.error('Checkout form or button not found');
        return;
    }

    // Wait for Midtrans script to load
    function waitForSnap(callback, maxAttempts = 50) {
        let attempts = 0;
        const interval = setInterval(function() {
            attempts++;
            if (window.snap && typeof window.snap.pay === 'function') {
                clearInterval(interval);
                console.log('Midtrans Snap.js loaded successfully');
                callback();
            } else if (attempts >= maxAttempts) {
                clearInterval(interval);
                console.error('Midtrans Snap.js failed to load');
                alert('Payment system is loading. Please refresh the page and try again.');
            }
        }, 100);
    }

    // Check payment status and redirect (with retry for webhook delay)
    async function checkPaymentStatusAndRedirect(orderNumber, redirectUrl, maxRetries = 3) {
        const csrfToken = document.querySelector('input[name="_token"]').value;
        
        for (let attempt = 1; attempt <= maxRetries; attempt++) {
            try {
                console.log(`Checking payment status (attempt ${attempt}/${maxRetries})...`);
                
                const response = await fetch('{{ route('payment.check-status') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        order_number: orderNumber
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    console.log('Payment status:', data.payment_status);
                    
                    // If status is paid, redirect immediately
                    if (data.payment_status === 'paid') {
                        console.log('Payment confirmed as paid, redirecting...');
                        window.location.href = redirectUrl;
                        return;
                    }
                    
                    // If still pending and not last attempt, wait a bit and retry
                    if (data.payment_status === 'pending' && attempt < maxRetries) {
                        console.log('Payment still pending, waiting for webhook...');
                        await new Promise(resolve => setTimeout(resolve, 2000)); // Wait 2 seconds
                        continue;
                    }
                }
                
                // If last attempt or status updated, redirect anyway
                if (attempt === maxRetries || data.status_updated) {
                    console.log('Redirecting to:', redirectUrl);
                    window.location.href = redirectUrl;
                    return;
                }
            } catch (error) {
                console.error('Error checking payment status:', error);
                // On error, redirect anyway (webhook will handle it)
                if (attempt === maxRetries) {
                    window.location.href = redirectUrl;
                }
            }
        }
    }

    checkoutForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Disable button
        placeOrderBtn.disabled = true;
        btnText.textContent = 'Processing...';

        try {
            // Get form data
            const formData = new FormData(checkoutForm);
            const csrfToken = document.querySelector('input[name="_token"]').value;

            console.log('Step 1: Creating order...');
            
            // Step 1: Create order via form submission
            const orderResponse = await fetch('{{ route('checkout.process') }}', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData
            });

            const orderResult = await orderResponse.json();
            console.log('Order response:', orderResult);

            if (!orderResponse.ok || !orderResult.success) {
                throw new Error(orderResult.message || 'Failed to create order');
            }

            // Extract order_number from response
            const orderNumber = orderResult.order_number;

            if (!orderNumber) {
                throw new Error('Order number not received');
            }

            console.log('Order created:', orderNumber);

            // Step 2: Get Snap token
            btnText.textContent = 'Preparing payment...';
            console.log('Step 2: Getting Snap token...');
            
            const tokenResponse = await fetch('{{ route('payment.snap-token') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    order_number: orderNumber
                })
            });

            const tokenResult = await tokenResponse.json();
            console.log('Token response:', tokenResult);

            if (!tokenResponse.ok || !tokenResult.success || !tokenResult.snap_token) {
                // Show detailed error message
                let errorMessage = tokenResult.message || 'Failed to generate payment token';
                if (tokenResult.debug && tokenResult.debug.error) {
                    console.error('Detailed error:', tokenResult.debug);
                    errorMessage += '\n\nDebug: ' + tokenResult.debug.error;
                }
                if (tokenResult.errors) {
                    console.error('Validation errors:', tokenResult.errors);
                    errorMessage += '\n\nErrors: ' + JSON.stringify(tokenResult.errors);
                }
                throw new Error(errorMessage);
            }

            console.log('Snap token received:', tokenResult.snap_token.substring(0, 20) + '...');

            // Step 3: Wait for Snap.js and open Midtrans payment popup
            btnText.textContent = 'Opening payment...';
            console.log('Step 3: Opening Midtrans payment popup...');

            waitForSnap(function() {
                window.snap.pay(tokenResult.snap_token, {
                    onSuccess: function(result) {
                        console.log('Payment success:', result);
                        // Payment successful - check status immediately and wait for webhook
                        checkPaymentStatusAndRedirect(orderNumber, `/order/success/${orderNumber}`);
                    },
                    onPending: function(result) {
                        console.log('Payment pending:', result);
                        // Payment pending - check status and redirect
                        checkPaymentStatusAndRedirect(orderNumber, '{{ route('orders.index') }}');
                    },
                    onError: function(result) {
                        console.error('Payment error:', result);
                        // Payment failed
                        alert('Payment failed. Please try again.');
                        placeOrderBtn.disabled = false;
                        btnText.textContent = 'Place Order';
                    },
                    onClose: function() {
                        console.log('Payment popup closed');
                        // User closed payment popup
                        alert('Payment not completed. You can complete the payment later from My Orders.');
                        placeOrderBtn.disabled = false;
                        btnText.textContent = 'Place Order';
                    }
                });
            });

        } catch (error) {
            console.error('Checkout error:', error);
            alert('Error: ' + error.message);
            placeOrderBtn.disabled = false;
            btnText.textContent = 'Place Order';
        }
    });
});
</script>
@endsection


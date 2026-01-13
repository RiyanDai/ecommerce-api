<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Midtrans\Config as MidtransConfig;
use Midtrans\Notification;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransController extends Controller
{
    public function __construct()
    {
        MidtransConfig::$serverKey = config('midtrans.server_key');
        MidtransConfig::$isProduction = config('midtrans.is_production');
        MidtransConfig::$isSanitized = true;
        MidtransConfig::$is3ds = true;
    }

    /**
     * Generate Snap Token for payment
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateSnapToken(Request $request)
    {
        $request->validate([
            'order_number' => 'required|string|exists:orders,order_number',
        ]);

        try {
            // Load order with relationships
            $order = Order::with(['user', 'orderItems.product'])
                ->where('order_number', $request->order_number)
                ->firstOrFail();

            // Ensure user owns the order
            if ($order->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this order',
                ], 403);
            }

            // Ensure payment status is pending (only pending orders can generate payment token)
            if ($order->payment_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order payment is not pending. Current payment status: ' . $order->payment_status,
                ], 400);
            }

            // Build transaction details
            // Add timestamp to order_id to ensure uniqueness and prevent error 2603 (duplicate order_id)
            // This allows regenerating payment token for the same order if QR code expires
            $timestamp = time();
            $uniqueOrderId = $order->order_number . '-' . $timestamp;
            
            // Store the order_id used for Midtrans in order metadata (we'll use a simple approach)
            // Save timestamp to help with status checking later
            $order->updated_at = now(); // Touch order to update timestamp
            $order->save();
            
            $transactionDetails = [
                'order_id' => $uniqueOrderId,
                'gross_amount' => (int) $order->total_amount,
            ];
            
            Log::info('Midtrans: Generated Snap token with order_id', [
                'order_number' => $order->order_number,
                'midtrans_order_id' => $uniqueOrderId,
                'timestamp' => $timestamp,
            ]);

            // Build customer details
            $customerDetails = [
                'first_name' => $order->user->name,
                'email' => $order->user->email,
            ];

            // Add phone if available
            if ($order->user->phone) {
                $customerDetails['phone'] = $order->user->phone;
            }

            // Validate order has items
            if ($order->orderItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order has no items',
                ], 400);
            }

            // Build item details from order items
            $itemDetails = [];
            $calculatedTotal = 0;
            
            foreach ($order->orderItems as $orderItem) {
                // Ensure product is loaded
                if (!$orderItem->product) {
                    Log::error('Midtrans: Product not found for order item', [
                        'order_item_id' => $orderItem->id,
                        'product_id' => $orderItem->product_id,
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Product not found for order item',
                    ], 400);
                }

                $itemPrice = (int) round($orderItem->price);
                $itemQuantity = (int) $orderItem->quantity;
                $itemSubtotal = $itemPrice * $itemQuantity;
                $calculatedTotal += $itemSubtotal;

                $itemDetails[] = [
                    'id' => (string) $orderItem->product_id,
                    'price' => $itemPrice,
                    'quantity' => $itemQuantity,
                    'name' => substr($orderItem->product->name, 0, 50), // Max 50 chars for Midtrans
                ];
            }

            // Validate total amount matches item details
            $grossAmount = (int) round($order->total_amount);
            if (abs($calculatedTotal - $grossAmount) > 1) { // Allow 1 rupiah difference due to rounding
                Log::warning('Midtrans: Total mismatch', [
                    'order_total' => $grossAmount,
                    'calculated_total' => $calculatedTotal,
                    'difference' => abs($calculatedTotal - $grossAmount),
                ]);
                // Use calculated total to ensure consistency
                $transactionDetails['gross_amount'] = $calculatedTotal;
            }

            // Validate item details not empty
            if (empty($itemDetails)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid items found in order',
                ], 400);
            }

            // Build Snap payload
        $params = [
                'transaction_details' => $transactionDetails,
                'customer_details' => $customerDetails,
                'item_details' => $itemDetails,
            ];

            // Log payload for debugging (remove sensitive data in production)
            Log::info('Midtrans Snap Token Request', [
                'order_number' => $order->order_number,
                'gross_amount' => $transactionDetails['gross_amount'],
                'item_count' => count($itemDetails),
            ]);

            // Generate Snap token
        $snapToken = Snap::getSnapToken($params);

        return response()->json([
                'success' => true,
                'snap_token' => $snapToken,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Midtrans Snap Token Generation Error', [
                'order_number' => $request->order_number ?? 'unknown',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate payment token: ' . $e->getMessage(),
                'debug' => config('app.debug') ? [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null,
            ], 500);
        }
    }

    /**
     * Handle Midtrans webhook notification
     * 
     * IMPORTANT: This is the ONLY place where payment_status can be updated.
     * Admin cannot manually change payment_status - it's controlled exclusively by Midtrans.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleNotification(Request $request)
    {
        // Log incoming request for debugging
        Log::info('Midtrans Webhook: Request received', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'content_type' => $request->header('Content-Type'),
            'has_transaction_status' => $request->has('transaction_status'),
            'has_order_id' => $request->has('order_id'),
            'all_data' => $request->all(),
            'raw_content' => $request->getContent(),
        ]);

        // Handle test request from Midtrans Dashboard
        // Midtrans sends a test POST with empty or minimal data when testing webhook URL
        if (empty($request->all()) || (!$request->has('transaction_status') && !$request->has('order_id'))) {
            Log::info('Midtrans Webhook: Test request received from dashboard');
            return response()->json([
                'success' => true,
                'message' => 'Webhook endpoint is working',
                'timestamp' => now()->toDateTimeString(),
            ], 200);
        }

        try {
            // Create notification instance from request
            // Midtrans SDK automatically verifies signature
            $notification = new Notification();

            // Get order from notification
            // order_id format: ORD-YYYYMMDD-XXXX-timestamp (we need to extract order_number)
            $orderIdFromNotification = $notification->order_id;
            $orderNumber = $orderIdFromNotification;
            
            // Extract order_number if order_id contains timestamp (format: ORDER_NUMBER-timestamp)
            // This handles the case where we added timestamp to prevent duplicate order_id error
            if (strpos($orderIdFromNotification, '-') !== false) {
                $parts = explode('-', $orderIdFromNotification);
                // Check if last part is timestamp (numeric and > 1000000000 = year 2001)
                $lastPart = end($parts);
                if (is_numeric($lastPart) && (int)$lastPart > 1000000000) {
                    // Remove timestamp part, reconstruct order_number
                    array_pop($parts);
                    $orderNumber = implode('-', $parts);
                }
            }
            
            $transactionStatus = $notification->transaction_status;
            $fraudStatus = $notification->fraud_status ?? null;

            // Log raw notification payload for audit trail
            Log::info('Midtrans Webhook Notification Received', [
                'order_id_from_notification' => $orderIdFromNotification,
                'extracted_order_number' => $orderNumber,
                'transaction_status' => $transactionStatus,
                'fraud_status' => $fraudStatus,
                'payment_type' => $notification->payment_type ?? null,
                'raw_notification' => $request->all(),
                'request_method' => $request->method(),
                'request_url' => $request->fullUrl(),
                'request_headers' => $request->headers->all(),
            ]);

            // Find order by order_number (extracted from order_id)
            $order = Order::where('order_number', $orderNumber)->first();

            // If order not found, try alternative search strategies
            if (!$order) {
                // Strategy 1: Try to find by partial order_number match
                // Sometimes order_id might have different format
                $orderNumberParts = explode('-', $orderNumber);
                if (count($orderNumberParts) >= 3) {
                    // Try with first 3 parts (ORD-YYYYMMDD-XXXX)
                    $partialOrderNumber = implode('-', array_slice($orderNumberParts, 0, 3));
                    $order = Order::where('order_number', $partialOrderNumber)->first();
                }
                
                // Strategy 2: Try to find by searching all orders and matching pattern
                if (!$order) {
                    $allOrders = Order::where('order_number', 'like', substr($orderNumber, 0, 15) . '%')
                        ->orderBy('created_at', 'desc')
                        ->limit(10)
                        ->get();
                    
                    foreach ($allOrders as $testOrder) {
                        if (strpos($orderIdFromNotification, $testOrder->order_number) !== false) {
                            $order = $testOrder;
                            Log::info('Midtrans Webhook: Order found by pattern matching', [
                                'order_id_from_notification' => $orderIdFromNotification,
                                'matched_order_number' => $testOrder->order_number,
                            ]);
                            break;
                        }
                    }
                }
            }

            if (!$order) {
                Log::warning('Midtrans Webhook: Order not found', [
                    'order_id_from_notification' => $orderIdFromNotification,
                    'extracted_order_number' => $orderNumber,
                    'recent_orders' => Order::orderBy('created_at', 'desc')
                        ->limit(5)
                        ->pluck('order_number')
                        ->toArray(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                    'debug' => config('app.debug') ? [
                        'order_id_from_notification' => $orderIdFromNotification,
                        'extracted_order_number' => $orderNumber,
                    ] : null,
                ], 404);
            }

            // Map Midtrans transaction status to payment_status
            // This is the ONLY place where payment_status is updated
            $newPaymentStatus = null;

            switch ($transactionStatus) {
                case 'settlement':
                    // Settlement means payment is confirmed
                    $newPaymentStatus = 'paid';
                    break;
                    
                case 'capture':
                    // Capture needs fraud status check
                    if ($fraudStatus === 'challenge') {
                        // Keep pending if challenged
                        $newPaymentStatus = 'pending';
                    } elseif ($fraudStatus === 'accept') {
                        $newPaymentStatus = 'paid';
                    } else {
                        // If fraud_status is null or unknown, treat as pending
                        $newPaymentStatus = 'pending';
                    }
                    break;

                case 'pending':
                    $newPaymentStatus = 'pending';
                    break;

                case 'expire':
                    $newPaymentStatus = 'expired';
                    break;

                case 'cancel':
                case 'deny':
                    $newPaymentStatus = 'failed';
                    break;

                default:
                    Log::info('Midtrans Webhook: Unhandled transaction status', [
                        'order_number' => $orderNumber,
                        'transaction_status' => $transactionStatus,
                    ]);
                    break;
            }

            // Idempotent update: Only update if status actually changed
            // This prevents duplicate updates and ensures webhook is safe to retry
            if ($newPaymentStatus && $order->payment_status !== $newPaymentStatus) {
                $oldPaymentStatus = $order->payment_status;
                
                // Update ONLY payment_status (never touch order_status here)
                $order->payment_status = $newPaymentStatus;
                $order->save();

                Log::info('Midtrans Webhook: Payment status updated', [
                    'order_number' => $orderNumber,
                    'old_payment_status' => $oldPaymentStatus,
                    'new_payment_status' => $newPaymentStatus,
                    'transaction_status' => $transactionStatus,
                ]);

                // If payment becomes paid and order is still new, keep order_status as 'new'
                // Admin will update order_status separately for fulfillment workflow
                if ($newPaymentStatus === 'paid' && $order->order_status === 'new') {
                    // Order status remains 'new' - admin will update it for fulfillment
                    Log::info('Midtrans Webhook: Order ready for fulfillment', [
                        'order_number' => $orderNumber,
                        'payment_status' => 'paid',
                        'order_status' => 'new',
                    ]);
                }
            } else {
                Log::info('Midtrans Webhook: No status change (idempotent)', [
                    'order_number' => $orderNumber,
                    'current_payment_status' => $order->payment_status,
                    'transaction_status' => $transactionStatus,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification processed',
            ]);

        } catch (\Exception $e) {
            Log::error('Midtrans Webhook Error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing notification',
            ], 500);
        }
    }

    /**
     * Check payment status from Midtrans API
     * 
     * This method queries Midtrans directly to get the latest payment status.
     * Useful when webhook hasn't been called yet or to manually refresh status.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPaymentStatus(Request $request)
    {
        $request->validate([
            'order_number' => 'required|string|exists:orders,order_number',
        ]);

        try {
            // Load order
            $order = Order::where('order_number', $request->order_number)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            // Try to get status from Midtrans
            // Note: Midtrans Transaction::status() requires the exact order_id used when creating transaction
            // Since we add timestamp, we need to try different approaches
            
            $status = null;
            $transactionData = null;
            $orderIdUsed = null;
            
            // Strategy 1: Try with order_number directly (in case transaction was created without timestamp)
            try {
                $status = Transaction::status($order->order_number);
                $transactionData = $status;
                $orderIdUsed = $order->order_number;
            } catch (\Exception $e) {
                Log::info('Midtrans: Direct order_number check failed, trying with timestamp variants', [
                    'order_number' => $order->order_number,
                    'error' => $e->getMessage(),
                ]);
                
                // Strategy 2: Try to find transaction by checking recent timestamps
                // Since we add timestamp when generating token, we need to try different timestamps
                // Use efficient search: check every 5 seconds for last 10 minutes, then every 30 seconds
                $found = false;
                $currentTime = time();
                $orderCreatedAt = strtotime($order->created_at);
                $orderUpdatedAt = strtotime($order->updated_at);
                
                // Search from order creation/update time to now
                // Check every 5 seconds for first 10 minutes (most recent transactions)
                $startTime = $currentTime;
                $tenMinutesAgo = $currentTime - 600; // 10 minutes ago
                $endTime = max($orderCreatedAt - 120, $orderUpdatedAt - 120, $currentTime - 3600); // Max 1 hour ago
                
                // First pass: check every 5 seconds for recent orders (last 10 minutes) - most likely to succeed
                for ($timestamp = $startTime; $timestamp >= max($tenMinutesAgo, $endTime) && !$found; $timestamp -= 5) {
                    try {
                        $testOrderId = $order->order_number . '-' . $timestamp;
                        $status = Transaction::status($testOrderId);
                        if ($status && isset($status->transaction_status)) {
                            $transactionData = $status;
                            $orderIdUsed = $testOrderId;
                            $found = true;
                            Log::info('Midtrans: Found transaction with timestamp (5s interval)', [
                                'order_id' => $testOrderId,
                                'timestamp' => $timestamp,
                                'order_created_at' => $order->created_at,
                                'order_updated_at' => $order->updated_at,
                            ]);
                            break;
                        }
                    } catch (\Exception $e) {
                        // Continue trying - transaction not found with this timestamp
                        continue;
                    }
                }
                
                // Second pass: if not found, check every 30 seconds for older orders (10-60 minutes ago)
                if (!$found && $endTime < $tenMinutesAgo) {
                    for ($timestamp = $tenMinutesAgo; $timestamp >= $endTime && !$found; $timestamp -= 30) {
                        try {
                            $testOrderId = $order->order_number . '-' . $timestamp;
                            $status = Transaction::status($testOrderId);
                            if ($status && isset($status->transaction_status)) {
                                $transactionData = $status;
                                $orderIdUsed = $testOrderId;
                                $found = true;
                                Log::info('Midtrans: Found transaction with timestamp (30s interval)', [
                                    'order_id' => $testOrderId,
                                    'timestamp' => $timestamp,
                                ]);
                                break;
                            }
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }
                
                if (!$found) {
                    Log::warning('Midtrans: Transaction not found with any timestamp', [
                        'order_number' => $order->order_number,
                        'order_created_at' => $order->created_at,
                        'searched_from' => date('Y-m-d H:i:s', $startTime),
                        'searched_to' => date('Y-m-d H:i:s', $endTime),
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'payment_status' => $order->payment_status,
                        'order_status' => $order->order_status,
                        'message' => 'Status retrieved from database. Transaction not found in Midtrans.',
                        'note' => 'If payment was completed via Midtrans simulator, webhook should update automatically. Please check webhook configuration in Midtrans dashboard.',
                        'webhook_url' => url('/payment/midtrans-webhook'),
                    ]);
                }
            }

            if (!$status) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found in Midtrans',
                ], 404);
            }

            // Extract transaction status from Midtrans response
            $transactionStatus = $status->transaction_status ?? null;
            $fraudStatus = $status->fraud_status ?? null;
            $orderIdFromMidtrans = $status->order_id ?? null;

            // Map Midtrans status to payment_status (same logic as webhook)
            $newPaymentStatus = null;

            switch ($transactionStatus) {
                case 'settlement':
                    // Settlement means payment is confirmed
                    $newPaymentStatus = 'paid';
                    break;
                    
                case 'capture':
                    // Capture needs fraud status check
                    if ($fraudStatus === 'challenge') {
                        $newPaymentStatus = 'pending';
                    } elseif ($fraudStatus === 'accept') {
                        $newPaymentStatus = 'paid';
                    } else {
                        // If fraud_status is null or unknown, treat as pending
                        $newPaymentStatus = 'pending';
                    }
                    break;

                case 'pending':
                    $newPaymentStatus = 'pending';
                    break;

                case 'expire':
                    $newPaymentStatus = 'expired';
                    break;

                case 'cancel':
                case 'deny':
                    $newPaymentStatus = 'failed';
                    break;

                default:
                    $newPaymentStatus = $order->payment_status; // Keep current
                    break;
            }

            // Update payment_status if changed
            $statusUpdated = false;
            if ($newPaymentStatus && $order->payment_status !== $newPaymentStatus) {
                $oldPaymentStatus = $order->payment_status;
                $order->payment_status = $newPaymentStatus;
                $order->save();
                $statusUpdated = true;

                Log::info('Midtrans: Payment status updated via API check', [
                    'order_number' => $order->order_number,
                    'old_payment_status' => $oldPaymentStatus,
                    'new_payment_status' => $newPaymentStatus,
                    'transaction_status' => $transactionStatus,
                ]);
            }

            return response()->json([
                'success' => true,
                'payment_status' => $order->payment_status,
                'order_status' => $order->order_status,
                'transaction_status' => $transactionStatus,
                'status_updated' => $statusUpdated,
                'message' => $statusUpdated 
                    ? 'Payment status updated successfully' 
                    : 'Payment status is up to date',
            ]);

        } catch (\Exception $e) {
            Log::error('Midtrans Check Payment Status Error', [
                'order_number' => $request->order_number ?? 'unknown',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check payment status: ' . $e->getMessage(),
            ], 500);
        }
    }
}

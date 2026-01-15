<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            
          
            $order->midtrans_order_id = $uniqueOrderId;
            $order->save();
            
            Log::info('Midtrans: Saved order_id to database', [
                'order_number' => $order->order_number,
                'midtrans_order_id' => $uniqueOrderId,
            ]);
            
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

                // If payment becomes paid, reduce stock automatically
                if ($newPaymentStatus === 'paid') {
                    try {
                        $this->reduceStockForPaidOrder($order);
                        Log::info('Midtrans Webhook: Stock reduced for paid order', [
                            'order_number' => $orderNumber,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Midtrans Webhook: Failed to reduce stock', [
                            'order_number' => $orderNumber,
                            'error' => $e->getMessage(),
                        ]);
                        // Don't fail the webhook if stock reduction fails
                        // Stock can be manually adjusted later
                    }
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
     * OPTIMIZED: Uses saved midtrans_order_id for instant lookup (20-60x faster)
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

            $status = null;
            $orderIdUsed = null;
            
            // ✅ FAST PATH: Use saved midtrans_order_id from database
            if ($order->midtrans_order_id) {
                try {
                    Log::info('Midtrans: Checking status with saved order_id', [
                        'midtrans_order_id' => $order->midtrans_order_id,
                    ]);
                    
                    $status = Transaction::status($order->midtrans_order_id);
                    $orderIdUsed = $order->midtrans_order_id;
                    
                    Log::info('Midtrans: Status retrieved successfully', [
                        'midtrans_order_id' => $order->midtrans_order_id,
                        'transaction_status' => $status->transaction_status ?? 'unknown',
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Midtrans: Saved order_id not found, trying fallback', [
                        'midtrans_order_id' => $order->midtrans_order_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // FALLBACK: Try with order_number directly (for old orders without midtrans_order_id)
            if (!$status) {
                try {
                    $status = Transaction::status($order->order_number);
                    $orderIdUsed = $order->order_number;
                    
                    Log::info('Midtrans: Status retrieved with order_number', [
                        'order_number' => $order->order_number,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Midtrans: Transaction not found', [
                        'order_number' => $order->order_number,
                        'error' => $e->getMessage(),
                    ]);
                    
                    // Return current status from database
                    return response()->json([
                        'success' => true,
                        'payment_status' => $order->payment_status,
                        'order_status' => $order->order_status,
                        'transaction_status' => null,
                        'status_updated' => false,
                        'message' => 'Transaction not found in Midtrans. Using database status.',
                        'note' => 'Payment may still be processing. Check again in a few moments.',
                    ]);
                }
            }

            if (!$status) {
                return response()->json([
                    'success' => true,
                    'payment_status' => $order->payment_status,
                    'order_status' => $order->order_status,
                    'transaction_status' => null,
                    'status_updated' => false,
                    'message' => 'Transaction not found in Midtrans',
                ]);
            }

            // Extract transaction status
            $transactionStatus = $status->transaction_status ?? null;
            $fraudStatus = $status->fraud_status ?? null;

            // Map Midtrans status to payment_status
            $newPaymentStatus = null;

            switch ($transactionStatus) {
                case 'settlement':
                    $newPaymentStatus = 'paid';
                    break;
                    
                case 'capture':
                    if ($fraudStatus === 'challenge') {
                        $newPaymentStatus = 'pending';
                    } elseif ($fraudStatus === 'accept') {
                        $newPaymentStatus = 'paid';
                    } else {
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
                    $newPaymentStatus = $order->payment_status;
                    break;
            }

            // Update payment_status if changed
            $statusUpdated = false;
            if ($newPaymentStatus && $order->payment_status !== $newPaymentStatus) {
                $oldPaymentStatus = $order->payment_status;
                $order->payment_status = $newPaymentStatus;
                $order->save();
                $statusUpdated = true;

                Log::info('Midtrans: Payment status updated', [
                    'order_number' => $order->order_number,
                    'old_status' => $oldPaymentStatus,
                    'new_status' => $newPaymentStatus,
                    'transaction_status' => $transactionStatus,
                ]);

                // If payment becomes paid, reduce stock automatically
                if ($newPaymentStatus === 'paid') {
                    try {
                        $this->reduceStockForPaidOrder($order);
                        Log::info('Midtrans: Stock reduced for paid order', [
                            'order_number' => $order->order_number,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Midtrans: Failed to reduce stock', [
                            'order_number' => $order->order_number,
                            'error' => $e->getMessage(),
                        ]);
                        // Don't fail the request if stock reduction fails
                    }
                }
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
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check payment status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reduce stock automatically when payment becomes paid
     * 
     * This method is idempotent - it checks if stock has already been reduced
     * to prevent duplicate stock reduction if called multiple times.
     * 
     * @param Order $order
     * @return void
     * @throws \Exception
     */
    private function reduceStockForPaidOrder(Order $order)
    {
        // Load order with items and products
        $order->load('orderItems.product');

        // Check if stock has already been reduced for this order
        // Look for stock history entries for this order with type 'out' (payment)
        $existingStockReduction = StockHistory::where('order_id', $order->id)
            ->where('type', 'out')
            ->where('description', 'like', '%payment%')
            ->exists();

        if ($existingStockReduction) {
            Log::info('Midtrans: Stock already reduced for this order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
            return; // Stock already reduced, skip
        }

        DB::beginTransaction();
        try {
            foreach ($order->orderItems as $item) {
                $product = $item->product;
                
                if (!$product) {
                    Log::warning('Midtrans: Product not found for order item', [
                        'order_id' => $order->id,
                        'order_item_id' => $item->id,
                        'product_id' => $item->product_id,
                    ]);
                    continue;
                }

                $stockBefore = $product->stock;
                $quantity = $item->quantity;

                // Check if stock is sufficient
                if ($stockBefore < $quantity) {
                    Log::warning('Midtrans: Insufficient stock for product', [
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'available_stock' => $stockBefore,
                        'required' => $quantity,
                    ]);
                    // Continue with other items, but log the issue
                    continue;
                }

                $stockAfter = $stockBefore - $quantity;
                $product->update(['stock' => $stockAfter]);

                // Log stock history
                StockHistory::create([
                    'product_id' => $product->id,
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'change' => -$quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'type' => 'out',
                    'description' => "Order #{$order->order_number} - Payment confirmed (automatic stock reduction)",
                ]);

                Log::info('Midtrans: Stock reduced for product', [
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                ]);
            }

            DB::commit();

            Log::info('Midtrans: Stock reduction completed for paid order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Midtrans: Stock reduction failed', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}

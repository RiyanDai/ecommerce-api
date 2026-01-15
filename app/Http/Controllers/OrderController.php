<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    // POST /api/checkout
    public function checkout(CheckoutRequest $request)
    {
        $user = $request->user();

        // Get user's cart items
        $carts = Cart::with('product')
            ->where('user_id', $user->id)
            ->get();

        if ($carts->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty',
                'errors' => ['cart' => ['Please add items to cart before checkout']],
            ], 422);
        }

        // Validate stock availability for all items
        $insufficientItems = [];
        foreach ($carts as $cart) {
            if ($cart->product->stock < $cart->quantity) {
                $insufficientItems[] = [
                    'product' => $cart->product->name,
                    'requested' => $cart->quantity,
                    'available' => $cart->product->stock,
                ];
            }
        }

        if (!empty($insufficientItems)) {
            return response()->json([
                'success' => false,
                'message' => 'Some items have insufficient stock',
                'errors' => ['stock' => $insufficientItems],
            ], 422);
        }

        // Generate unique order number: ORD-YYYYMMDD-XXXX
        $orderNumber = $this->generateOrderNumber();

        DB::beginTransaction();
        try {
            // Calculate total
            $totalAmount = $carts->sum(function ($cart) {
                return $cart->quantity * $cart->product->price;
            });

            // Create order with initial statuses
            // payment_status: 'pending' (will be updated by Midtrans webhook)
            // order_status: 'new' (will be updated by admin for fulfillment)
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $orderNumber,
                'payment_status' => 'pending',  // Controlled by Midtrans webhook
                'order_status' => 'new',         // Controlled by admin
                'total_amount' => $totalAmount,
            ]);

            // Create order items
            foreach ($carts as $cart) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cart->product_id,
                    'quantity' => $cart->quantity,
                    'price' => $cart->product->price,
                    'subtotal' => $cart->quantity * $cart->product->price,
                ]);
            }

            // Clear cart
            Cart::where('user_id', $user->id)->delete();

            DB::commit();

            $order->load('orderItems.product');

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => new OrderResource($order),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order',
                'errors' => ['error' => [$e->getMessage()]],
            ], 500);
        }
    }

    /**
     * Generate unique order number: ORD-YYYYMMDD-XXXX
     */
    private function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $lastOrder = Order::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        if ($lastOrder) {
            // Extract sequence from last order number
            $parts = explode('-', $lastOrder->order_number);
            $lastSequence = (int) end($parts);
            $sequence = str_pad($lastSequence + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $sequence = '0001';
        }

        return "ORD-{$date}-{$sequence}";
    }

    public function index(Request $request)
    {
        $query = Order::with('orderItems.product')
            ->where('user_id', $request->user()->id);
        
        // Filter by payment_status
        if ($paymentStatus = $request->query('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }
        
        // Filter by order_status
        if ($orderStatus = $request->query('order_status')) {
            $query->where('order_status', $orderStatus);
        }
        
        $orders = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders),
        ]);
    }

    public function show(Request $request, $id)
    {
        $order = Order::with('orderItems.product')
            ->where('user_id', $request->user()->id)
            ->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * Cancel order
     * 
     * Can cancel order even if midtrans_order_id exists, as long as:
     * - Payment status is still 'pending' (not paid yet)
     * - Order status is 'new' (not yet processed)
     * 
     * Supports both POST and PUT methods for Flutter compatibility
     */
    public function cancel(Request $request, $id)
    {
        $order = Order::where('user_id', $request->user()->id)
            ->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        Log::info('Order cancellation requested', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'midtrans_order_id' => $order->midtrans_order_id,
            'payment_status' => $order->payment_status,
            'order_status' => $order->order_status,
        ]);

        // Business rule: Only pending payment and new order can be cancelled
        // Note: Order can be cancelled even if midtrans_order_id exists, as long as payment is still pending
        if (!$order->isPaymentPending()) {
            Log::warning('Order cancellation rejected: Payment not pending', [
                'order_id' => $order->id,
                'payment_status' => $order->payment_status,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Order cannot be cancelled. Payment status: ' . ucfirst($order->payment_status),
            ], 400);
        }

        if (!$order->isOrderNew()) {
            Log::warning('Order cancellation rejected: Order not new', [
                'order_id' => $order->id,
                'order_status' => $order->order_status,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Order cannot be cancelled. Order status: ' . ucfirst($order->order_status),
            ], 400);
        }

        // Update payment status to failed (customer-initiated cancellation)
        $order->payment_status = 'failed';
        $order->save();

        Log::info('Order cancelled successfully', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'midtrans_order_id' => $order->midtrans_order_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully',
        ]);
    }



}

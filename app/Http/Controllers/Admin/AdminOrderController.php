<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminOrderController extends Controller
{
    // GET /api/admin/orders - List all orders (filter by payment_status or order_status)
    public function index(Request $request)
    {
        $query = Order::with(['user', 'orderItems.product']);

        // Filter by payment_status or order_status
        if ($paymentStatus = $request->query('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }
        
        if ($orderStatus = $request->query('order_status')) {
            $query->where('order_status', $orderStatus);
        }

        // Legacy support: if 'status' is used, treat as order_status
        if ($status = $request->query('status')) {
            $query->where('order_status', $status);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Orders fetched',
            'data' => OrderResource::collection($orders)->response()->getData(true),
        ]);
    }

    // GET /api/admin/orders/{id} - Order detail
    public function show($id)
    {
        $order = Order::with(['user', 'orderItems.product.category'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Order detail fetched',
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * PUT /api/admin/orders/{id}/status - Update order fulfillment status
     * 
     * IMPORTANT: Admin can ONLY update order_status (fulfillment workflow).
     * payment_status is controlled EXCLUSIVELY by Midtrans webhook.
     */
    public function updateStatus(UpdateOrderStatusRequest $request, $id)
    {
        $order = Order::with('orderItems.product')->findOrFail($id);
        $newOrderStatus = $request->validated()['order_status'];
        $oldOrderStatus = $order->order_status;

        // Business Rule: Order can only be fulfilled if payment is paid
        if (!$order->isPaymentPaid() && $newOrderStatus !== 'new') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update order status. Payment must be paid first.',
                'errors' => [
                    'payment_status' => ['Current payment status: ' . ucfirst($order->payment_status)],
                ],
            ], 422);
        }

        DB::beginTransaction();
        try {
            // If changing to completed: reduce stock
            if ($newOrderStatus === 'completed' && $oldOrderStatus !== 'completed') {
                // Additional check: ensure payment is paid
                if (!$order->isPaymentPaid()) {
                    throw new \Exception('Cannot complete order. Payment must be paid first.');
                }
                $this->reduceStockForOrder($order, $request->user());
            }

            // If changing to refunded: return stock
            if ($newOrderStatus === 'refunded' && $oldOrderStatus !== 'refunded') {
                $this->returnStockForOrder($order, $request->user());
            }

            // Update ONLY order_status (never touch payment_status)
            $order->order_status = $newOrderStatus;
            $order->save();
            $order->load(['user', 'orderItems.product']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order fulfillment status updated',
                'data' => new OrderResource($order),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status',
                'errors' => ['error' => [$e->getMessage()]],
            ], 500);
        }
    }

    /**
     * Reduce stock when order is completed
     */
    private function reduceStockForOrder(Order $order, $user)
    {
        foreach ($order->orderItems as $item) {
            $product = $item->product;
            $stockBefore = $product->stock;
            $quantity = $item->quantity;

            // Check if stock is sufficient
            if ($stockBefore < $quantity) {
                throw new \Exception("Insufficient stock for product: {$product->name}. Available: {$stockBefore}, Required: {$quantity}");
            }

            $stockAfter = $stockBefore - $quantity;
            $product->update(['stock' => $stockAfter]);

            // Log stock history
            StockHistory::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'order_id' => $order->id,
                'change' => -$quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'type' => 'out',
                'description' => "Order #{$order->order_number} completed",
            ]);
        }
    }

    /**
     * Return stock when order is cancelled (if was completed)
     */
    private function returnStockForOrder(Order $order, $user)
    {
        foreach ($order->orderItems as $item) {
            $product = $item->product;
            $stockBefore = $product->stock;
            $quantity = $item->quantity;
            $stockAfter = $stockBefore + $quantity;

            $product->update(['stock' => $stockAfter]);

            // Log stock history
            StockHistory::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'order_id' => $order->id,
                'change' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'type' => 'in',
                'description' => "Order #{$order->order_number} refunded - stock returned",
            ]);
        }
    }
}

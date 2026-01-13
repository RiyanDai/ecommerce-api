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
    // GET /api/admin/orders - List all orders (filter by status)
    public function index(Request $request)
    {
        $query = Order::with(['user', 'orderItems.product']);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
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

    // PUT /api/admin/orders/{id}/status - Update status
    public function updateStatus(UpdateOrderStatusRequest $request, $id)
    {
        $order = Order::with('orderItems.product')->findOrFail($id);
        $newStatus = $request->validated()['status'];
        $oldStatus = $order->status;

        DB::beginTransaction();
        try {
            // If changing to completed: reduce stock
            if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                $this->reduceStockForOrder($order, $request->user());
            }

            // If cancelling a completed order: return stock
            if ($newStatus === 'cancelled' && $oldStatus === 'completed') {
                $this->returnStockForOrder($order, $request->user());
            }

            $order->update(['status' => $newStatus]);
            $order->load(['user', 'orderItems.product']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order status updated',
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
                'description' => "Order #{$order->order_number} cancelled - stock returned",
            ]);
        }
    }
}

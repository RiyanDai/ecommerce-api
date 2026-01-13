<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminOrderWebController extends Controller
{
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

        return view('admin.orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::with(['user', 'orderItems.product.category'])->findOrFail($id);
        return view('admin.orders.show', compact('order'));
    }

    /**
     * Update order fulfillment status (order_status)
     * 
     * IMPORTANT: Admin can ONLY update order_status (fulfillment workflow).
     * payment_status is controlled EXCLUSIVELY by Midtrans webhook and cannot be changed here.
     * 
     * Business Rules:
     * - Order can only be fulfilled if payment_status is 'paid'
     * - Stock is reduced when order_status changes to 'completed'
     * - Stock is returned when order_status changes to 'refunded'
     */
    public function updateStatus(UpdateOrderStatusRequest $request, $id)
    {
        $order = Order::with('orderItems.product')->findOrFail($id);
        $newOrderStatus = $request->validated()['order_status'];
        $oldOrderStatus = $order->order_status;

        // Business Rule: Order can only be fulfilled if payment is paid
        if (!$order->isPaymentPaid() && $newOrderStatus !== 'new') {
            return redirect()->back()
                ->with('error', 'Cannot update order status. Payment must be paid first. Current payment status: ' . ucfirst($order->payment_status));
        }

        try {
            // If changing to completed: reduce stock
            if ($newOrderStatus === 'completed' && $oldOrderStatus !== 'completed') {
                // Additional check: ensure payment is paid
                if (!$order->isPaymentPaid()) {
                    return redirect()->back()
                        ->with('error', 'Cannot complete order. Payment must be paid first.');
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

            return redirect()->route('admin.orders.show', $order->id)
                ->with('success', 'Order fulfillment status updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update order status: ' . $e->getMessage());
        }
    }

    private function reduceStockForOrder(Order $order, $user)
    {
        foreach ($order->orderItems as $item) {
            $product = $item->product;
            $stockBefore = $product->stock;
            $quantity = $item->quantity;

            if ($stockBefore < $quantity) {
                throw new \Exception("Insufficient stock for product: {$product->name}");
            }

            $stockAfter = $stockBefore - $quantity;
            $product->update(['stock' => $stockAfter]);

            \App\Models\StockHistory::create([
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

    private function returnStockForOrder(Order $order, $user)
    {
        foreach ($order->orderItems as $item) {
            $product = $item->product;
            $stockBefore = $product->stock;
            $quantity = $item->quantity;
            $stockAfter = $stockBefore + $quantity;

            $product->update(['stock' => $stockAfter]);

            \App\Models\StockHistory::create([
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

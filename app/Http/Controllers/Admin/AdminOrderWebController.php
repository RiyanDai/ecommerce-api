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

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::with(['user', 'orderItems.product.category'])->findOrFail($id);
        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, $id)
    {
        $order = Order::with('orderItems.product')->findOrFail($id);
        $newStatus = $request->validated()['status'];
        $oldStatus = $order->status;

        // Use the same logic from AdminOrderController
        $adminOrderController = new \App\Http\Controllers\Admin\AdminOrderController();
        
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

            return redirect()->route('admin.orders.show', $order->id)
                ->with('success', 'Order status updated successfully.');
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

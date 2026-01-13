<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('orderItems.product')
            ->where('user_id', auth()->id());

        // Filter by payment_status
        if ($paymentStatus = $request->query('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }
        
        // Filter by order_status
        if ($orderStatus = $request->query('order_status')) {
            $query->where('order_status', $orderStatus);
        }

        // Legacy support: if 'status' is used, treat as order_status
        if ($status = $request->query('status')) {
            $query->where('order_status', $status);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::with(['orderItems.product.category', 'user'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return view('orders.show', compact('order'));
    }

    /**
     * Cancel order
     * 
     * Customers can only cancel orders if:
     * - Payment is still pending (not paid yet)
     * - Order status is new (not yet processed)
     */
    public function cancel($id)
    {
        $order = Order::where('user_id', auth()->id())
            ->findOrFail($id);

        // Business rule: Can only cancel if payment is pending and order is new
        if (!$order->isPaymentPending()) {
            return back()->with('error', 'Cannot cancel order. Payment has already been processed.');
        }

        if (!$order->isOrderNew()) {
            return back()->with('error', 'Cannot cancel order. Order is already being processed.');
        }

        // Update payment_status to failed (cancellation = failed payment)
        // Note: This is customer-initiated cancellation, not Midtrans cancellation
        // In production, you might want to call Midtrans cancel API first
        $order->payment_status = 'failed';
        $order->save();

        return back()->with('success', 'Order cancelled successfully!');
    }
}

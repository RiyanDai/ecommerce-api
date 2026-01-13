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

        if ($status = $request->query('status')) {
            $query->where('status', $status);
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

    public function cancel($id)
    {
        $order = Order::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->findOrFail($id);

        $order->update(['status' => 'cancelled']);

        return back()->with('success', 'Order cancelled successfully!');
    }
}

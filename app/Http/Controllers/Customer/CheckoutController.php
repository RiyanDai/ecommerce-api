<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutProcessRequest;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function index()
    {
        $carts = Cart::with('product')->where('user_id', auth()->id())->get();

        if ($carts->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty!');
        }

        // Validate stock
        foreach ($carts as $cart) {
            if ($cart->product->stock < $cart->quantity) {
                return redirect()->route('cart.index')
                    ->with('error', "Insufficient stock for {$cart->product->name}. Available: {$cart->product->stock}");
            }
        }

        $subtotal = $carts->sum(function ($cart) {
            return $cart->quantity * $cart->product->price;
        });

        $shipping = 0;
        $total = $subtotal + $shipping;

        return view('checkout.index', compact('carts', 'subtotal', 'shipping', 'total'));
    }

    public function process(CheckoutProcessRequest $request)
    {
        $carts = Cart::with('product')->where('user_id', auth()->id())->get();

        if ($carts->isEmpty()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your cart is empty!',
                ], 422);
            }
            return redirect()->route('cart.index')->with('error', 'Your cart is empty!');
        }

        // Validate stock again
        foreach ($carts as $cart) {
            if ($cart->product->stock < $cart->quantity) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for {$cart->product->name}",
                    ], 422);
                }
                return redirect()->route('cart.index')
                    ->with('error', "Insufficient stock for {$cart->product->name}");
            }
        }

        DB::beginTransaction();
        try {
            // Generate order number
            $orderNumber = $this->generateOrderNumber();

            // Calculate total
            $totalAmount = $carts->sum(function ($cart) {
                return $cart->quantity * $cart->product->price;
            });

            // Create order with initial statuses
            // payment_status: 'pending' (will be updated by Midtrans webhook)
            // order_status: 'new' (will be updated by admin for fulfillment)
            $order = Order::create([
                'user_id' => auth()->id(),
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
            Cart::where('user_id', auth()->id())->delete();

            DB::commit();

            // Reload order with relationships for validation
            $order->load(['orderItems.product', 'user']);

            // Return JSON for AJAX requests (payment flow)
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order created successfully',
                    'order_number' => $order->order_number,
                    'order_id' => $order->id,
                ]);
            }

            // Regular form submission (fallback)
            return redirect()->route('order.success', $order->order_number)
                ->with('success', 'Order placed successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to process order. Please try again.',
                ], 500);
            }
            
            return back()->with('error', 'Failed to process order. Please try again.');
        }
    }

    public function success($orderNumber)
    {
        $order = Order::with(['orderItems.product', 'user'])
            ->where('order_number', $orderNumber)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return view('checkout.success', compact('order'));
    }

    private function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $lastOrder = Order::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        if ($lastOrder) {
            $parts = explode('-', $lastOrder->order_number);
            $lastSequence = (int) end($parts);
            $sequence = str_pad($lastSequence + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $sequence = '0001';
        }

        return "ORD-{$date}-{$sequence}";
    }
}

<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $carts = Cart::with('product.category')
            ->where('user_id', auth()->id())
            ->get();

        $subtotal = $carts->sum(function ($cart) {
            return $cart->quantity * $cart->product->price;
        });

        $shipping = 0; // Free shipping
        $total = $subtotal + $shipping;

        return view('cart.index', compact('carts', 'subtotal', 'shipping', 'total'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::findOrFail($request->product_id);

        // Check stock
        if ($product->stock < $request->quantity) {
            return back()->with('error', 'Insufficient stock. Available: ' . $product->stock);
        }

        // Check if already in cart
        $cart = Cart::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->first();

        if ($cart) {
            $newQuantity = $cart->quantity + $request->quantity;
            if ($product->stock < $newQuantity) {
                return back()->with('error', 'Insufficient stock. Available: ' . $product->stock);
            }
            $cart->update(['quantity' => $newQuantity]);
        } else {
            Cart::create([
                'user_id' => auth()->id(),
                'product_id' => $product->id,
                'quantity' => $request->quantity,
            ]);
        }

        return back()->with('success', 'Product added to cart!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = Cart::where('user_id', auth()->id())->findOrFail($id);

        if ($cart->product->stock < $request->quantity) {
            return back()->with('error', 'Insufficient stock. Available: ' . $cart->product->stock);
        }

        $cart->update(['quantity' => $request->quantity]);

        return back()->with('success', 'Cart updated!');
    }

    public function remove($id)
    {
        $cart = Cart::where('user_id', auth()->id())->findOrFail($id);
        $cart->delete();

        return back()->with('success', 'Item removed from cart!');
    }
}

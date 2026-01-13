<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartStoreRequest;
use App\Http\Requests\CartUpdateRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    // GET /api/cart - View cart
    public function index(Request $request)
    {
        $carts = Cart::with('product.category')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $total = $carts->sum(function ($cart) {
            return $cart->quantity * $cart->product->price;
        });

        return response()->json([
            'success' => true,
            'message' => 'Cart fetched',
            'data' => [
                'items' => CartResource::collection($carts),
                'total' => $total,
            ],
        ]);
    }

    // POST /api/cart - Add to cart (validate stock)
    public function store(CartStoreRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();
        $product = Product::findOrFail($data['product_id']);

        // Validate stock availability
        if ($product->stock < $data['quantity']) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock',
                'errors' => [
                    'quantity' => ['Available stock: ' . $product->stock],
                ],
            ], 422);
        }

        // Check if product already in cart
        $cart = Cart::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($cart) {
            // Update quantity if adding more
            $newQuantity = $cart->quantity + $data['quantity'];
            
            if ($product->stock < $newQuantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock',
                    'errors' => [
                        'quantity' => ['Available stock: ' . $product->stock . ', Current in cart: ' . $cart->quantity],
                    ],
                ], 422);
            }

            $cart->update(['quantity' => $newQuantity]);
            $cart->load('product.category');
        } else {
            // Create new cart item
            $cart = Cart::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'quantity' => $data['quantity'],
            ]);
            $cart->load('product.category');
        }

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart',
            'data' => new CartResource($cart),
        ], 201);
    }

    // PUT /api/cart/{id} - Update quantity
    public function update(CartUpdateRequest $request, $id)
    {
        $cart = Cart::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $data = $request->validated();
        $product = $cart->product;

        // Validate stock availability
        if ($product->stock < $data['quantity']) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock',
                'errors' => [
                    'quantity' => ['Available stock: ' . $product->stock],
                ],
            ], 422);
        }

        $cart->update(['quantity' => $data['quantity']]);
        $cart->load('product.category');

        return response()->json([
            'success' => true,
            'message' => 'Cart updated',
            'data' => new CartResource($cart),
        ]);
    }

    // DELETE /api/cart/{id} - Remove item
    public function destroy(Request $request, $id)
    {
        $cart = Cart::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $cart->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart',
            'data' => null,
        ]);
    }
}

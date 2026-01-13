<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductAddStockRequest;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    // CUSTOMER: list with pagination, search, filter by category
    public function index(Request $request)
    {
        $query = Product::with('category')
            ->where('is_active', true);

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Products fetched',
            'data' => ProductResource::collection($products)->response()->getData(true),
        ]);
    }

    // CUSTOMER: detail
    public function show($id)
    {
        $product = Product::with('category')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Product detail fetched',
            'data' => new ProductResource($product),
        ]);
    }

    // ADMIN: create
    public function store(ProductStoreRequest $request)
    {
        $data = $request->validated();

        $data['slug'] = $data['slug'] ?? Str::slug($data['name'] . '-' . Str::random(4));

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Product created',
            'data' => new ProductResource($product->load('category')),
        ], 201);
    }

    // ADMIN: update
    public function update(ProductUpdateRequest $request, $id)
    {
        $product = Product::findOrFail($id);
        $data = $request->validated();

        if (isset($data['name']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name'] . '-' . Str::random(4));
        }

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Product updated',
            'data' => new ProductResource($product->load('category')),
        ]);
    }

    // ADMIN: delete
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted',
            'data' => null,
        ]);
    }

    // ADMIN: add stock
    public function addStock(ProductAddStockRequest $request, $id)
    {
        $product = Product::findOrFail($id);
        $qty = $request->validated()['quantity'];

        DB::transaction(function () use ($product, $qty, $request) {
            $before = $product->stock;
            $product->increment('stock', $qty);

            StockHistory::create([
                'product_id' => $product->id,
                'user_id' => $request->user()->id,
                'change' => $qty,
                'stock_before' => $before,
                'stock_after' => $before + $qty,
                'type' => 'in',
                'description' => $request->input('description', 'Stock added'),
            ]);
        });

        $product->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Stock added',
            'data' => new ProductResource($product->load('category')),
        ]);
    }
}
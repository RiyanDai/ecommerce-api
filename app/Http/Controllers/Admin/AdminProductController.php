<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductAddStockRequest;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(15);
        $categories = Category::all();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('admin.products.create', compact('categories'));
    }

    public function store(ProductStoreRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name'] . '-' . Str::random(4));

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        Product::create($data);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $categories = Category::all();
        return view('admin.products.edit', compact('product', 'categories'));
    }

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

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }

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

        return redirect()->back()
            ->with('success', 'Stock added successfully.');
    }
}

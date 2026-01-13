<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category')->where('is_active', true);

        // Search
        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Filter by category
        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(12);
        $categories = Category::all();

        return view('shop.index', compact('products', 'categories'));
    }

    public function show($slug)
    {
        $product = Product::with('category')->where('slug', $slug)->where('is_active', true)->firstOrFail();
        
        // Related products (same category, exclude current)
        $relatedProducts = Product::with('category')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->limit(4)
            ->get();

        return view('shop.show', compact('product', 'relatedProducts'));
    }
}

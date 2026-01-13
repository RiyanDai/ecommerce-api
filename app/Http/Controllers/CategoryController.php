<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;

class CategoryController extends Controller
{
    // GET /api/categories
    public function index()
    {
        $categories = Category::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'message' => 'Categories fetched',
            'data' => CategoryResource::collection($categories),
        ]);
    }
}
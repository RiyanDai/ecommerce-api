<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockHistory;
use Illuminate\Http\Request;

class AdminStockWebController extends Controller
{
    public function index(Request $request)
    {
        $query = StockHistory::with(['product', 'user', 'order']);

        if ($productId = $request->query('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($orderId = $request->query('order_id')) {
            $query->where('order_id', $orderId);
        }

        $histories = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.stock-history.index', compact('histories'));
    }
}

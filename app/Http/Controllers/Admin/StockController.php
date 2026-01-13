<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\StockHistoryResource;
use App\Models\StockHistory;
use Illuminate\Http\Request;

class StockController extends Controller
{
    // GET /api/admin/stock-history - View stock history
    public function index(Request $request)
    {
        $query = StockHistory::with(['product', 'user', 'order']);

        // Filter by product
        if ($productId = $request->query('product_id')) {
            $query->where('product_id', $productId);
        }

        // Filter by type
        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        // Filter by order
        if ($orderId = $request->query('order_id')) {
            $query->where('order_id', $orderId);
        }

        $histories = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Stock history fetched',
            'data' => StockHistoryResource::collection($histories)->response()->getData(true),
        ]);
    }
}

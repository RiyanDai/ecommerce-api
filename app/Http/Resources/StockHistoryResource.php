<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'user' => new UserResource($this->whenLoaded('user')),
            'change' => $this->change,
            'stock_before' => $this->stock_before,
            'stock_after' => $this->stock_after,
            'type' => $this->type,
            'description' => $this->description,
            'order_id' => $this->order_id,
            'created_at' => $this->created_at,
        ];
    }
}

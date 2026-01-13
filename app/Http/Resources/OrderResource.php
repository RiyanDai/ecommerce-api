<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'payment_status' => $this->payment_status,  // Controlled by Midtrans webhook
            'order_status' => $this->order_status,        // Controlled by admin
            'total_amount' => $this->total_amount,
            'user' => new UserResource($this->whenLoaded('user')),
            'order_items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

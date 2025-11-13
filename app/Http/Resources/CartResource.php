<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subtotal = $this->items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });

        return [
            'id' => $this->id,
            'session_id' => $this->when(!Auth::check(), $this->session_id),
            'total_items' => $this->items->sum('quantity'),
            'subtotal' => $subtotal,
            'total_weight' => $this->items->sum('total_weight'),
            'items' => CartItemResource::collection($this->whenLoaded('items')),
        ];
    }
}

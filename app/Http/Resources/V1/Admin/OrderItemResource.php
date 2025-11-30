<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_name' => $this->product->name ?? null,
            'quantity' => $this->quantity,
            'price' => $this->unit_price,
            'subtotal' => $this->sub_total,
            'variant_name' => $this->variant->name ?? null,
        ];
    }
}

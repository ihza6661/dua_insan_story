<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'price' => $this->price,
            'stock' => $this->stock,
            'weight' => $this->weight,
            'options' => AttributeValueResource::collection($this->whenLoaded('options')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
        ];
    }
}

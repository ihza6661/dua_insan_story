<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'sub_total' => $this->sub_total,
            'product' => new ProductResource($this->product),
            'variant' => new ProductVariantResource($this->variant),
            'can_review' => $this->canBeReviewed(),
            'review' => $this->review ? [
                'id' => $this->review->id,
                'rating' => $this->review->rating,
                'comment' => $this->review->comment,
                'is_approved' => $this->review->is_approved,
                'created_at' => $this->review->created_at,
            ] : null,
        ];
    }
}

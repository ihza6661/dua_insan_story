<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'is_verified' => $this->is_verified,
            'is_approved' => $this->is_approved,
            'is_featured' => $this->is_featured,
            'helpful_count' => $this->helpful_count,
            'admin_response' => $this->admin_response,
            'admin_responded_at' => $this->admin_responded_at?->toDateTimeString(),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),

            // Frontend compatibility: add status field
            'status' => $this->is_approved ? 'approved' : 'rejected',
            'user_id' => $this->customer_id,
            'user_name' => $this->customer->full_name ?? 'Anonymous',
            'product_id' => $this->product_id,
            'product_name' => $this->product->name ?? null,
            'order_item_id' => $this->order_item_id,
            'is_verified_purchase' => $this->is_verified,

            // Relationships
            'customer' => [
                'id' => $this->customer->id ?? null,
                'full_name' => $this->customer->full_name ?? 'Anonymous',
            ],
            'product' => [
                'id' => $this->product->id ?? null,
                'name' => $this->product->name ?? null,
            ],
            'admin_responder' => $this->when($this->adminResponder, [
                'id' => $this->adminResponder->id ?? null,
                'full_name' => $this->adminResponder->full_name ?? null,
            ]),
            'images' => ReviewImageResource::collection($this->whenLoaded('images')),
        ];
    }
}

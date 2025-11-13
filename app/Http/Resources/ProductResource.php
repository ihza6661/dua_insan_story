<?php

namespace App\Http\Resources;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $variants = $this->whenLoaded('variants');
        $defaultVariant = ($variants instanceof Collection && $variants->isNotEmpty()) ? $variants->first() : null;

        $featuredImage = null;
        if ($defaultVariant instanceof ProductVariant && $defaultVariant->relationLoaded('images')) {
            $featuredImage = $defaultVariant->images->firstWhere('is_featured', true) ?? $defaultVariant->images->first();
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'base_price' => $this->base_price,
            'weight' => $this->weight,
            'min_order_quantity' => $this->min_order_quantity,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toDateTimeString(),
            'category' => new ProductCategoryResource($this->whenLoaded('category')),
            'featured_image' => new ProductImageResource($featuredImage),
            'add_ons' => AddOnResource::collection($this->whenLoaded('addOns')),

            // DATA BARU UNTUK MEMUDAHKAN FRONTEND
            'grouped_options' => $this->whenLoaded('variants', function () {
                return $this->variants
                    ->flatMap(fn($variant) => $variant->options) // Ambil semua options dari semua varian
                    ->unique('id') // Buang nilai yang duplikat (misal: "Merah" muncul 2x)
                    ->groupBy('attribute.name') // Kelompokkan berdasarkan nama atribut induk
                    ->map(function ($values) {
                        return AttributeValueResource::collection($values); // Format setiap nilai
                    });
            }),

            'variants' => ProductVariantResource::collection($variants),
        ];
    }
}

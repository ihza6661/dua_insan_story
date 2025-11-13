<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'unit_price',
        'customization_details',
    ];

    protected $appends = [
        'total_weight',
    ];

    protected function casts(): array
    {
        return [
            'customization_details' => 'array',
        ];
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getSelectedAddOnIdsAttribute(): array
    {
        $addOnEntries = collect($this->customization_details['add_ons'] ?? []);
        $ids = $addOnEntries->pluck('id')->filter()->values();

        if ($ids->isNotEmpty()) {
            return $ids->all();
        }

        $names = $addOnEntries->pluck('name')->filter()->values();

        if ($names->isEmpty()) {
            return [];
        }

        $this->ensureProductAddOnsLoaded();

        return $this->product?->addOns
            ?->filter(fn ($addOn) => $names->contains($addOn->name))
            ->pluck('id')
            ->all() ?? [];
    }

    public function getAddOnsAttribute()
    {
        $this->ensureProductAddOnsLoaded();

        $addOnIds = $this->selected_add_on_ids;

        if (empty($addOnIds)) {
            return collect();
        }

        return $this->product?->addOns?->whereIn('id', $addOnIds)->values() ?? collect();
    }

    public function getTotalWeightAttribute(): int
    {
        $this->ensureProductAddOnsLoaded();
        $this->loadMissing('variant');

        $variantWeight = $this->variant?->weight;
        $productWeight = (int) ($this->product->weight ?? 0);
        $baseWeight = $variantWeight !== null ? (int) $variantWeight : $productWeight;
        $addOnWeight = $this->addOns->sum(fn ($addOn) => (int) ($addOn->pivot->weight ?? $addOn->weight ?? 0));

        return ($baseWeight + $addOnWeight) * $this->quantity;
    }

    protected function ensureProductAddOnsLoaded(): void
    {
        if (!$this->relationLoaded('product')) {
            $this->loadMissing('product.addOns');
            return;
        }

        if ($this->product && !$this->product->relationLoaded('addOns')) {
            $this->product->loadMissing('addOns');
        }
    }
}

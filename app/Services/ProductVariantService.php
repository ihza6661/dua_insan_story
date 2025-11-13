<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Exception;
use Illuminate\Support\Facades\DB;

class ProductVariantService
{
    public function createVariant(Product $product, array $data): ProductVariant
    {
        $optionIds = $data['options'];
        sort($optionIds);

        $existingVariant = $product->variants()
            ->whereHas('options', function ($query) use ($optionIds) {
                $query->whereIn('attribute_value_id', $optionIds);
            }, '=', count($optionIds))
            ->first();

        if ($existingVariant) {
            throw new Exception('Kombinasi opsi untuk varian ini sudah ada.');
        }

        return DB::transaction(function () use ($product, $data, $optionIds) {
            $variant = $product->variants()->create([
                'price' => $data['price'],
                'stock' => $data['stock'] ?? 0,
                'weight' => $data['weight'] ?? null,
            ]);

            $variant->options()->attach($optionIds);

            return $variant;
        });
    }

    public function updateVariant(ProductVariant $variant, array $data): ProductVariant
    {
        $variant->update($data);
        return $variant->fresh(['options.attribute', 'images']);
    }

    public function deleteVariant(ProductVariant $variant): void
    {
        $variant->delete();
    }
}

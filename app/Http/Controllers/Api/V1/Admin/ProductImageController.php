<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ProductImage\StoreRequest;
use App\Http\Resources\ProductImageResource;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class ProductImageController extends Controller
{
    public function store(StoreRequest $request, ProductVariant $variant, ProductService $productService): JsonResponse
    {
        try {
            $validated = $request->validated();
            $uploadedImages = [];

            $existingImageCount = $variant->images()->count();
            $existingHasFeatured = $variant->images()->where('is_featured', true)->exists();

            if (isset($validated['is_featured_index'])) {
                $featuredIndex = (int) $validated['is_featured_index'];
            } else {
                $featuredIndex = $existingHasFeatured ? null : 0;
            }

            foreach ($validated['images'] as $index => $imageFile) {
                $providedAltText = $validated['alt_texts'][$index] ?? null;
                $altText = $providedAltText;

                $isFeaturedForThisImage = ($featuredIndex !== null && $featuredIndex == $index);

                $imageData = [
                    'alt_text' => $altText,
                    'is_featured' => $isFeaturedForThisImage,
                ];

                $uploadedImages[] = $productService->addImageToVariant(
                    $variant,
                    $imageFile,
                    $imageData
                );
            }

            return response()->json([
                'message' => count($uploadedImages).' gambar berhasil diunggah ke varian.',
                'data' => ProductImageResource::collection($uploadedImages),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengunggah gambar: '.$e->getMessage(),
            ], 500);
        }
    }

    public function destroy(ProductImage $image, ProductService $productService): JsonResponse
    {
        $productService->deleteProductImage($image);

        return response()->json([
            'message' => 'Gambar berhasil dihapus.',
        ]);
    }
}

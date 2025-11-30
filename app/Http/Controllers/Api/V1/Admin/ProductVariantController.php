<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ProductVariant\StoreRequest;
use App\Http\Requests\Api\V1\Admin\ProductVariant\UpdateRequest;
use App\Http\Resources\ProductVariantResource;
use App\Models\Product;
use App\Models\ProductVariant as ModelProductVariant;
use App\Services\ProductVariantService;
use Illuminate\Http\JsonResponse;

class ProductVariantController extends Controller
{
    protected $variantService;

    public function __construct(ProductVariantService $variantService)
    {
        $this->variantService = $variantService;
    }

    public function store(StoreRequest $request, Product $product): JsonResponse
    {
        try {
            $variant = $this->variantService->createVariant($product, $request->validated());

            return response()->json([
                'message' => 'Varian produk berhasil dibuat.',
                'data' => new ProductVariantResource($variant),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    public function show(ModelProductVariant $variant): ProductVariantResource
    {
        $variant->load(['options.attribute', 'images']);

        return new ProductVariantResource($variant);
    }

    public function update(UpdateRequest $request, ModelProductVariant $variant): JsonResponse
    {
        $variant = $this->variantService->updateVariant($variant, $request->validated());

        return response()->json([
            'message' => 'Varian produk berhasil diperbarui.',
            'data' => new ProductVariantResource($variant),
        ]);
    }

    public function destroy(ModelProductVariant $variant): JsonResponse
    {
        $this->variantService->deleteVariant($variant);

        return response()->json(['message' => 'Varian produk berhasil dihapus.']);
    }
}

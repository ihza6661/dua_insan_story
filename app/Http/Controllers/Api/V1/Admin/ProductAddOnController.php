<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ProductAddOn\StoreRequest;
use App\Models\AddOn;
use App\Models\Product;
use App\Services\ProductAddOnService;
use Illuminate\Http\JsonResponse;

class ProductAddOnController extends Controller
{
    protected $productAddOnService;

    public function __construct(ProductAddOnService $productAddOnService)
    {
        $this->productAddOnService = $productAddOnService;
    }

    public function store(StoreRequest $request, Product $product): JsonResponse
    {
        $this->productAddOnService->attachAddOn($product, $request->validated());

        return response()->json(['message' => 'Item tambahan berhasil ditautkan ke produk.']);
    }

    public function destroy(Product $product, AddOn $addOn): JsonResponse
    {
        $this->productAddOnService->detachAddOn($product, $addOn);

        return response()->json(['message' => 'Tautan item tambahan berhasil dihapus dari produk.']);
    }
}

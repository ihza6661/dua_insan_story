<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Product\StoreRequest;
use App\Http\Requests\Api\V1\Admin\Product\UpdateRequest;
use App\Http\Resources\AdminProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(): AnonymousResourceCollection
    {

        $products = Product::with(['category', 'variants.images'])->latest()->get();

        return AdminProductResource::collection($products);

    }

    public function store(StoreRequest $request, ProductService $productService): JsonResponse
    {

        $product = $productService->createProduct($request->validated());

        return response()->json([

            'message' => 'Produk berhasil dibuat.',

            'data' => new AdminProductResource($product->load('category')),

        ], 201);

    }

    public function show(Product $product): AdminProductResource
    {

        $product->load(['category', 'variants.options.attribute', 'variants.images', 'addOns']);

        return new AdminProductResource($product);

    }

    public function update(UpdateRequest $request, Product $product, ProductService $productService): JsonResponse
    {

        $updatedProduct = $productService->updateProduct($product, $request->validated());

        return response()->json([

            'message' => 'Produk berhasil diperbarui.',

            'data' => new AdminProductResource($updatedProduct->load('category')),

        ]);

    }

    public function destroy(Product $product, ProductService $productService): JsonResponse
    {

        try {

            $productService->deleteProduct($product);

        } catch (\Exception $e) {

            return response()->json([

                'message' => $e->getMessage(),

            ], 409);

        }

        return response()->json([

            'message' => 'Produk berhasil dihapus.',

        ]);

    }
}

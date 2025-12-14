<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ProductCategory\StoreRequest;
use App\Http\Requests\Api\V1\Admin\ProductCategory\UpdateRequest;
use App\Http\Resources\AdminProductCategoryResource;
use App\Models\ProductCategory;
use App\Services\CacheService;
use App\Services\ProductCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductCategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $perPage = request('per_page', 20);
        $page = request('page', 1);

        // Cache category listing
        $cacheKey = "categories.admin.list.pp{$perPage}.p{$page}";
        $categories = CacheService::remember(
            CacheService::TAG_CATEGORIES,
            $cacheKey,
            CacheService::TTL_LONG,
            function () use ($perPage) {
                return ProductCategory::withCount('products')
                    ->latest()
                    ->paginate($perPage);
            }
        );

        return AdminProductCategoryResource::collection($categories);
    }

    public function store(StoreRequest $request, ProductCategoryService $categoryService): JsonResponse
    {
        $category = $categoryService->createCategory($request->validated());

        // Invalidate categories cache
        CacheService::invalidateCategories();

        return response()->json([
            'message' => 'Kategori produk berhasil dibuat.',
            'data' => new AdminProductCategoryResource($category),
        ], 201);
    }

    public function show(ProductCategory $productCategory): AdminProductCategoryResource
    {
        return new AdminProductCategoryResource($productCategory);
    }

    public function update(UpdateRequest $request, ProductCategory $productCategory, ProductCategoryService $categoryService): JsonResponse
    {
        $updatedCategory = $categoryService->updateCategory($productCategory, $request->validated());

        // Invalidate categories cache
        CacheService::invalidateCategories();

        return response()->json([
            'message' => 'Kategori produk berhasil diperbarui.',
            'data' => new AdminProductCategoryResource($updatedCategory),
        ]);
    }

    public function destroy(ProductCategory $productCategory): JsonResponse
    {
        if ($productCategory->products()->exists()) {
            return response()->json([
                'message' => 'Kategori tidak dapat dihapus karena masih memiliki produk terkait.',
            ], 409);
        }

        $productCategory->delete();

        // Invalidate categories cache
        CacheService::invalidateCategories();

        return response()->json([
            'message' => 'Kategori produk berhasil dihapus.',
        ]);
    }
}

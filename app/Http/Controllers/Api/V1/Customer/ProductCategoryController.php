<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCategoryResource;
use App\Services\Customer\ProductCategoryService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductCategoryController extends Controller
{
    protected $categoryService;

    public function __construct(ProductCategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index(): AnonymousResourceCollection
    {
        $categories = $this->categoryService->getAllCategories();

        return ProductCategoryResource::collection($categories);
    }

    public function show(string $id): ProductCategoryResource
    {
        $category = $this->categoryService->findCategoryById((int) $id);

        return new ProductCategoryResource($category);
    }
}

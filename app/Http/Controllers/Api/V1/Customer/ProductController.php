<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $searchTerm = $request->query('search');

        $categorySlug = $request->query('category');
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        $sort = $request->query('sort');

        $products = $this->productService->getPaginatedActiveProducts($searchTerm, $categorySlug, $minPrice, $maxPrice, $sort);

        return ProductResource::collection($products);
    }

    public function show(string $identifier): ProductResource
    {
        // Support both ID and slug
        $product = $this->productService->findPubliclyVisibleProduct($identifier);

        $product->load(['category', 'variants.options.attribute', 'variants.images', 'addOns']);

        return new ProductResource($product);
    }
}

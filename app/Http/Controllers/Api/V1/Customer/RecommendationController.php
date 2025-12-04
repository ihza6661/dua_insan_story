<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Services\ProductRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RecommendationController extends Controller
{
    protected ProductRecommendationService $recommendationService;

    public function __construct(ProductRecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    /**
     * Get personalized recommendations for authenticated user
     * Falls back to popular products for guests
     */
    public function personalized(): JsonResponse
    {
        $userId = Auth::id();
        $recommendations = $this->recommendationService->getPersonalizedRecommendations($userId, 8);

        return response()->json([
            'message' => 'Personalized recommendations retrieved successfully',
            'data' => $recommendations->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'base_price' => $product->base_price,
                    'category' => $product->category,
                    'images' => $product->images,
                    'is_active' => $product->is_active,
                ];
            }),
        ]);
    }

    /**
     * Get popular products (most ordered)
     */
    public function popular(): JsonResponse
    {
        $popular = $this->recommendationService->getPopularProducts(8);

        return response()->json([
            'message' => 'Popular products retrieved successfully',
            'data' => $popular->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'base_price' => $product->base_price,
                    'category' => $product->category,
                    'images' => $product->images,
                    'is_active' => $product->is_active,
                    'order_count' => $product->order_count ?? 0,
                ];
            }),
        ]);
    }

    /**
     * Get similar products based on category
     */
    public function similar(int $productId): JsonResponse
    {
        $similar = $this->recommendationService->getSimilarProducts($productId, 4);

        return response()->json([
            'message' => 'Similar products retrieved successfully',
            'data' => $similar->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'base_price' => $product->base_price,
                    'category' => $product->category,
                    'images' => $product->images,
                    'is_active' => $product->is_active,
                ];
            }),
        ]);
    }

    /**
     * Get trending products (recently popular)
     */
    public function trending(): JsonResponse
    {
        $trending = $this->recommendationService->getTrendingProducts(8, 30);

        return response()->json([
            'message' => 'Trending products retrieved successfully',
            'data' => $trending->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'base_price' => $product->base_price,
                    'category' => $product->category,
                    'images' => $product->images,
                    'is_active' => $product->is_active,
                    'recent_order_count' => $product->recent_order_count ?? 0,
                ];
            }),
        ]);
    }

    /**
     * Get new arrivals
     */
    public function newArrivals(): JsonResponse
    {
        $newArrivals = $this->recommendationService->getNewArrivals(8);

        return response()->json([
            'message' => 'New arrivals retrieved successfully',
            'data' => $newArrivals->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'base_price' => $product->base_price,
                    'category' => $product->category,
                    'images' => $product->images,
                    'is_active' => $product->is_active,
                    'created_at' => $product->created_at,
                ];
            }),
        ]);
    }
}

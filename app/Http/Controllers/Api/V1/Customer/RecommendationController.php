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
            'data' => $recommendations->map(fn ($product) => $this->formatProductWithImage($product)),
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
                $formatted = $this->formatProductWithImage($product);
                $formatted['order_count'] = $product->order_count ?? 0;

                return $formatted;
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
            'data' => $similar->map(fn ($product) => $this->formatProductWithImage($product)),
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
                $formatted = $this->formatProductWithImage($product);
                $formatted['recent_order_count'] = $product->recent_order_count ?? 0;

                return $formatted;
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
                $formatted = $this->formatProductWithImage($product);
                $formatted['created_at'] = $product->created_at;

                return $formatted;
            }),
        ]);
    }

    /**
     * Helper method to format product with proper image handling
     * Handles both physical products (with images) and digital products (with template)
     */
    protected function formatProductWithImage($product): array
    {
        $featuredImage = null;

        // For digital products, get image from template
        if ($product->product_type === 'digital' && $product->template) {
            $featuredImage = [
                'id' => null,
                'image' => null,
                'image_url' => url('media/'.$product->template->thumbnail_image),
                'alt_text' => $product->template->name,
                'is_featured' => true,
            ];
        } else {
            // For physical products, get from images relationship
            $imageModel = $product->images->firstWhere('is_featured', true) ?? $product->images->first();
            if ($imageModel) {
                $featuredImage = [
                    'id' => $imageModel->id,
                    'image' => $imageModel->image,
                    'image_url' => $imageModel->image_url,
                    'alt_text' => $imageModel->alt_text,
                    'is_featured' => $imageModel->is_featured,
                ];
            }
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'base_price' => $product->base_price,
            'product_type' => $product->product_type,
            'category' => $product->category,
            'featured_image' => $featuredImage,
            'is_active' => $product->is_active,
        ];
    }
}

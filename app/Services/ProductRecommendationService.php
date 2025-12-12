<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductRecommendationService
{
    /**
     * Get personalized product recommendations for a user
     * Based on order history and popular products
     */
    public function getPersonalizedRecommendations(?int $userId = null, int $limit = 8): Collection
    {
        $cacheKey = $userId ? "recommendations.user.{$userId}.{$limit}" : "recommendations.popular.{$limit}";

        return Cache::remember($cacheKey, now()->addHours(2), function () use ($userId, $limit) {
            if ($userId) {
                return $this->getUserBasedRecommendations($userId, $limit);
            }

            return $this->getPopularProducts($limit);
        });
    }

    /**
     * Get recommendations based on user's order history
     */
    protected function getUserBasedRecommendations(int $userId, int $limit): Collection
    {
        // Get user's purchased product categories
        $purchasedCategories = $this->getUserPurchasedCategories($userId);

        if ($purchasedCategories->isEmpty()) {
            return $this->getPopularProducts($limit);
        }

        // Get products from same categories that user hasn't purchased
        $recommendations = Product::query()
            ->whereIn('category_id', $purchasedCategories->pluck('id'))
            ->whereNotIn('id', $this->getUserPurchasedProductIds($userId))
            ->with(['category', 'images', 'template'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        // If not enough recommendations, fill with popular products
        if ($recommendations->count() < $limit) {
            $remaining = $limit - $recommendations->count();
            $popular = $this->getPopularProducts($remaining, $recommendations->pluck('id')->toArray());
            $recommendations = $recommendations->merge($popular);
        }

        return $recommendations;
    }

    /**
     * Get product categories user has purchased from
     */
    protected function getUserPurchasedCategories(int $userId): Collection
    {
        return DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->where('orders.customer_id', $userId)
            ->where('orders.order_status', '!=', Order::STATUS_CANCELLED)
            ->select('product_categories.id', 'product_categories.name')
            ->distinct()
            ->get();
    }

    /**
     * Get IDs of products user has already purchased
     */
    protected function getUserPurchasedProductIds(int $userId): array
    {
        return DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.customer_id', $userId)
            ->where('orders.order_status', '!=', Order::STATUS_CANCELLED)
            ->pluck('order_items.product_id')
            ->unique()
            ->toArray();
    }

    /**
     * Get popular products (most ordered)
     */
    public function getPopularProducts(int $limit = 8, array $excludeIds = []): Collection
    {
        $cacheKey = "recommendations.popular.{$limit}.".md5(json_encode($excludeIds));

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($limit, $excludeIds) {
            return Product::query()
                ->select('products.*', DB::raw('COUNT(order_items.id) as order_count'))
                ->join('order_items', 'products.id', '=', 'order_items.product_id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.order_status', '!=', Order::STATUS_CANCELLED)
                ->when(! empty($excludeIds), function ($query) use ($excludeIds) {
                    $query->whereNotIn('products.id', $excludeIds);
                })
                ->with(['category', 'images', 'template'])
                ->groupBy('products.id')
                ->orderByDesc('order_count')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get similar products based on category
     */
    public function getSimilarProducts(int $productId, int $limit = 4): Collection
    {
        $cacheKey = "recommendations.similar.{$productId}.{$limit}";

        return Cache::remember($cacheKey, now()->addHours(4), function () use ($productId, $limit) {
            $product = Product::find($productId);

            if (! $product) {
                return collect();
            }

            return Product::query()
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $productId)
                ->with(['category', 'images', 'template'])
                ->inRandomOrder()
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get trending products (recently popular)
     */
    public function getTrendingProducts(int $limit = 8, int $daysBack = 30): Collection
    {
        $cacheKey = "recommendations.trending.{$limit}.{$daysBack}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($limit, $daysBack) {
            return Product::query()
                ->select('products.*', DB::raw('COUNT(order_items.id) as recent_order_count'))
                ->join('order_items', 'products.id', '=', 'order_items.product_id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.created_at', '>=', now()->subDays($daysBack))
                ->where('orders.order_status', '!=', Order::STATUS_CANCELLED)
                ->with(['category', 'images', 'template'])
                ->groupBy('products.id')
                ->orderByDesc('recent_order_count')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get new arrivals (recently added products)
     */
    public function getNewArrivals(int $limit = 8): Collection
    {
        $cacheKey = "recommendations.new_arrivals.{$limit}";

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($limit) {
            return Product::query()
                ->with(['category', 'images', 'template'])
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get products from a specific category
     */
    public function getCategoryProducts(int $categoryId, int $limit = 8, ?int $excludeProductId = null): Collection
    {
        $cacheKey = "recommendations.category.{$categoryId}.{$limit}.".($excludeProductId ?? 'all');

        return Cache::remember($cacheKey, now()->addHours(4), function () use ($categoryId, $limit, $excludeProductId) {
            return Product::query()
                ->where('category_id', $categoryId)
                ->when($excludeProductId, function ($query) use ($excludeProductId) {
                    $query->where('id', '!=', $excludeProductId);
                })
                ->with(['category', 'images', 'template'])
                ->inRandomOrder()
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Clear recommendation cache for a user
     */
    public function clearUserCache(int $userId): void
    {
        Cache::forget("recommendations.user.{$userId}.8");
        Cache::forget("recommendations.user.{$userId}.4");
    }

    /**
     * Clear all recommendation caches
     */
    public function clearAllCaches(): void
    {
        // In production, use more selective cache clearing with tags
        Cache::flush();
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Centralized cache management service with tagging support
 */
class CacheService
{
    /**
     * Cache tags for different data types
     */
    public const TAG_PRODUCTS = 'products';
    public const TAG_CATEGORIES = 'categories';
    public const TAG_ORDERS = 'orders';
    public const TAG_REVIEWS = 'reviews';
    public const TAG_DASHBOARD = 'dashboard';
    public const TAG_USERS = 'users';
    public const TAG_RECOMMENDATIONS = 'recommendations';
    public const TAG_PAYMENTS = 'payments';
    public const TAG_INVITATIONS = 'invitations';

    /**
     * Cache TTL constants (in seconds)
     */
    public const TTL_SHORT = 300;      // 5 minutes
    public const TTL_MEDIUM = 1800;    // 30 minutes
    public const TTL_LONG = 3600;      // 1 hour
    public const TTL_VERY_LONG = 21600; // 6 hours
    public const TTL_DAY = 86400;      // 24 hours

    /**
     * Remember a value with cache tags
     *
     * @param array|string $tags
     * @param string $key
     * @param int $ttl
     * @param callable $callback
     * @return mixed
     */
    public static function remember(array|string $tags, string $key, int $ttl, callable $callback): mixed
    {
        $tags = is_array($tags) ? $tags : [$tags];

        // Check if using Redis (supports tags), otherwise use standard cache
        if (config('cache.default') === 'redis') {
            return Cache::tags($tags)->remember($key, $ttl, $callback);
        }

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Forget a specific cache key within tags
     *
     * @param array|string $tags
     * @param string $key
     * @return bool
     */
    public static function forget(array|string $tags, string $key): bool
    {
        $tags = is_array($tags) ? $tags : [$tags];

        if (config('cache.default') === 'redis') {
            return Cache::tags($tags)->forget($key);
        }

        return Cache::forget($key);
    }

    /**
     * Flush all cache for specific tags
     *
     * @param array|string $tags
     * @return bool
     */
    public static function flushTags(array|string $tags): bool
    {
        $tags = is_array($tags) ? $tags : [$tags];

        if (config('cache.default') === 'redis') {
            return Cache::tags($tags)->flush();
        }

        // Fallback: flush entire cache if tags not supported
        return Cache::flush();
    }

    /**
     * Invalidate product-related caches
     */
    public static function invalidateProducts(): void
    {
        static::flushTags([self::TAG_PRODUCTS, self::TAG_RECOMMENDATIONS]);
    }

    /**
     * Invalidate category-related caches
     */
    public static function invalidateCategories(): void
    {
        static::flushTags([self::TAG_CATEGORIES, self::TAG_PRODUCTS]);
    }

    /**
     * Invalidate order-related caches
     */
    public static function invalidateOrders(): void
    {
        static::flushTags([self::TAG_ORDERS, self::TAG_DASHBOARD, self::TAG_PAYMENTS]);
    }

    /**
     * Invalidate review-related caches
     */
    public static function invalidateReviews(): void
    {
        static::flushTags(self::TAG_REVIEWS);
    }

    /**
     * Invalidate dashboard caches
     */
    public static function invalidateDashboard(): void
    {
        static::flushTags(self::TAG_DASHBOARD);
    }

    /**
     * Invalidate user-specific recommendations
     *
     * @param int $userId
     */
    public static function invalidateUserRecommendations(int $userId): void
    {
        static::forget(self::TAG_RECOMMENDATIONS, "recommendations.user.{$userId}.8");
        static::forget(self::TAG_RECOMMENDATIONS, "recommendations.user.{$userId}.4");
    }

    /**
     * Generate cache key for product listing
     *
     * @param array $filters
     * @param int $perPage
     * @return string
     */
    public static function productListingKey(array $filters, int $perPage): string
    {
        return 'products.list.' . md5(json_encode($filters)) . ".page_{$perPage}";
    }

    /**
     * Generate cache key for category products
     *
     * @param int $categoryId
     * @param int $perPage
     * @return string
     */
    public static function categoryProductsKey(int $categoryId, int $perPage): string
    {
        return "products.category.{$categoryId}.page_{$perPage}";
    }

    /**
     * Check if cache is healthy (for monitoring)
     *
     * @return bool
     */
    public static function healthCheck(): bool
    {
        try {
            $testKey = 'cache.health.check';
            Cache::put($testKey, true, 60);
            $result = Cache::get($testKey);
            Cache::forget($testKey);

            return $result === true;
        } catch (\Exception) {
            return false;
        }
    }
}

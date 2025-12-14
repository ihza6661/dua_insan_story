<?php

namespace App\Console\Commands;

use App\Services\CacheService;
use App\Services\ProductRecommendationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WarmCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm 
                            {--products : Warm product listings cache}
                            {--categories : Warm categories cache}
                            {--recommendations : Warm recommendations cache}
                            {--all : Warm all caches}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up application caches with frequently accessed data';

    /**
     * Execute the console command.
     */
    public function handle(ProductRecommendationService $recommendationService): int
    {
        $warmAll = $this->option('all');

        $this->info('🔥 Warming up caches...');
        $this->newLine();

        // Warm product listings
        if ($warmAll || $this->option('products')) {
            $this->warmProductListings();
        }

        // Warm categories
        if ($warmAll || $this->option('categories')) {
            $this->warmCategories();
        }

        // Warm recommendations
        if ($warmAll || $this->option('recommendations')) {
            $this->warmRecommendations($recommendationService);
        }

        // If no options specified, warm all
        if (! $warmAll && ! $this->option('products') && ! $this->option('categories') && ! $this->option('recommendations')) {
            $this->warmProductListings();
            $this->warmCategories();
            $this->warmRecommendations($recommendationService);
        }

        $this->newLine();
        $this->info('✅ Cache warming complete!');

        return Command::SUCCESS;
    }

    /**
     * Warm product listings cache
     */
    protected function warmProductListings(): void
    {
        $this->line('Warming product listings...');

        try {
            // Warm first page of products
            $cacheKey = CacheService::productListingKey([], 10) . '.p1';
            CacheService::remember(
                CacheService::TAG_PRODUCTS,
                $cacheKey,
                CacheService::TTL_MEDIUM,
                function () {
                    return DB::table('products')
                        ->where('is_active', true)
                        ->orderByDesc('created_at')
                        ->limit(10)
                        ->get();
                }
            );

            $this->info('  ✓ Product listings warmed');
        } catch (\Exception $e) {
            $this->error('  ✗ Failed to warm product listings: '.$e->getMessage());
        }
    }

    /**
     * Warm categories cache
     */
    protected function warmCategories(): void
    {
        $this->line('Warming categories...');

        try {
            $cacheKey = 'categories.admin.list.pp20.p1';
            CacheService::remember(
                CacheService::TAG_CATEGORIES,
                $cacheKey,
                CacheService::TTL_LONG,
                function () {
                    return DB::table('product_categories')
                        ->orderByDesc('created_at')
                        ->limit(20)
                        ->get();
                }
            );

            $this->info('  ✓ Categories warmed');
        } catch (\Exception $e) {
            $this->error('  ✗ Failed to warm categories: '.$e->getMessage());
        }
    }

    /**
     * Warm recommendations cache
     */
    protected function warmRecommendations(ProductRecommendationService $recommendationService): void
    {
        $this->line('Warming recommendations...');

        try {
            // Warm popular products
            $recommendationService->getPopularProducts(8);
            $this->info('  ✓ Popular products warmed');

            // Warm new arrivals
            $recommendationService->getNewArrivals(8);
            $this->info('  ✓ New arrivals warmed');

            // Warm trending products
            $recommendationService->getTrendingProducts(8, 30);
            $this->info('  ✓ Trending products warmed');
        } catch (\Exception $e) {
            $this->error('  ✗ Failed to warm recommendations: '.$e->getMessage());
        }
    }
}

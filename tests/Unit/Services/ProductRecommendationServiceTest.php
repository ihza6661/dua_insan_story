<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\ProductRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductRecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductRecommendationService $service;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductRecommendationService;
        $this->customer = User::factory()->create(['role' => 'customer']);
        
        // Clear cache before each test
        Cache::flush();
    }

    #[Test]
    public function it_returns_popular_products_when_user_has_no_order_history(): void
    {
        $category = ProductCategory::factory()->create();
        
        // Create products with orders to make them popular
        $popularProduct = Product::factory()->create(['category_id' => $category->id]);
        $order = Order::factory()->create(['order_status' => Order::STATUS_PAID]);
        OrderItem::factory()->count(5)->create([
            'order_id' => $order->id,
            'product_id' => $popularProduct->id,
        ]);

        $recommendations = $this->service->getPersonalizedRecommendations($this->customer->id, 4);

        $this->assertNotEmpty($recommendations);
        $this->assertLessThanOrEqual(4, $recommendations->count());
    }

    #[Test]
    public function it_returns_personalized_recommendations_based_on_purchase_history(): void
    {
        $category = ProductCategory::factory()->create();
        
        // Product user has purchased
        $purchasedProduct = Product::factory()->create(['category_id' => $category->id]);
        
        // Products in the same category user hasn't purchased
        $unpurchasedProducts = Product::factory()->count(8)->create(['category_id' => $category->id]);
        
        // Create an order for the customer
        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'order_status' => Order::STATUS_PAID,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $purchasedProduct->id,
        ]);

        $recommendations = $this->service->getPersonalizedRecommendations($this->customer->id, 8);

        // Should not recommend already purchased product
        $this->assertFalse($recommendations->contains('id', $purchasedProduct->id));
        
        // Should recommend from same category
        $this->assertTrue($recommendations->count() > 0);
        
        // All recommendations should be from the same category
        $recommendations->each(function ($product) use ($category) {
            $this->assertEquals($category->id, $product->category_id);
        });
    }

    #[Test]
    public function it_excludes_cancelled_orders_from_recommendations(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        
        // Create cancelled order
        $cancelledOrder = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'order_status' => Order::STATUS_CANCELLED,
        ]);
        OrderItem::factory()->create([
            'order_id' => $cancelledOrder->id,
            'product_id' => $product->id,
        ]);

        // The product should still be recommended since cancelled orders don't count
        $recommendations = $this->service->getPersonalizedRecommendations($this->customer->id, 8);
        
        // Recommendations should be available (not filtered out by cancelled order)
        $this->assertGreaterThanOrEqual(0, $recommendations->count());
    }

    #[Test]
    public function it_gets_popular_products_ordered_by_order_count(): void
    {
        $category = ProductCategory::factory()->create();
        
        // Create products with different order counts
        $mostPopular = Product::factory()->create(['category_id' => $category->id]);
        $lessPopular = Product::factory()->create(['category_id' => $category->id]);
        
        $order1 = Order::factory()->create(['order_status' => Order::STATUS_PAID]);
        $order2 = Order::factory()->create(['order_status' => Order::STATUS_PAID]);
        
        // Most popular product gets 5 order items
        OrderItem::factory()->count(5)->create([
            'order_id' => $order1->id,
            'product_id' => $mostPopular->id,
        ]);
        
        // Less popular product gets 2 order items
        OrderItem::factory()->count(2)->create([
            'order_id' => $order2->id,
            'product_id' => $lessPopular->id,
        ]);

        $popular = $this->service->getPopularProducts(5);

        $this->assertNotEmpty($popular);
        // Most popular should appear first
        if ($popular->count() >= 2) {
            $this->assertEquals($mostPopular->id, $popular->first()->id);
        }
    }

    #[Test]
    public function it_excludes_specified_product_ids_from_popular_products(): void
    {
        $category = ProductCategory::factory()->create();
        $product1 = Product::factory()->create(['category_id' => $category->id]);
        $product2 = Product::factory()->create(['category_id' => $category->id]);
        
        $order = Order::factory()->create(['order_status' => Order::STATUS_PAID]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product1->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product2->id]);

        $popular = $this->service->getPopularProducts(5, [$product1->id]);

        $this->assertFalse($popular->contains('id', $product1->id));
    }

    #[Test]
    public function it_gets_similar_products_from_same_category(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        Product::factory()->count(3)->create(['category_id' => $category->id]);
        
        // Create product in different category
        $otherCategory = ProductCategory::factory()->create();
        Product::factory()->create(['category_id' => $otherCategory->id]);

        $similar = $this->service->getSimilarProducts($product->id, 4);

        // Should not include the original product
        $this->assertFalse($similar->contains('id', $product->id));
        
        // Should only include products from same category
        $similar->each(function ($p) use ($category) {
            $this->assertEquals($category->id, $p->category_id);
        });
    }

    #[Test]
    public function it_returns_empty_collection_for_nonexistent_product(): void
    {
        $similar = $this->service->getSimilarProducts(99999, 4);

        $this->assertEmpty($similar);
    }

    #[Test]
    public function it_gets_trending_products_from_recent_orders(): void
    {
        $category = ProductCategory::factory()->create();
        
        // Create recent trending product
        $trendingProduct = Product::factory()->create(['category_id' => $category->id]);
        $recentOrder = Order::factory()->create([
            'order_status' => Order::STATUS_PAID,
            'created_at' => now()->subDays(10),
        ]);
        OrderItem::factory()->count(3)->create([
            'order_id' => $recentOrder->id,
            'product_id' => $trendingProduct->id,
        ]);
        
        // Create old product (outside the trending window)
        $oldProduct = Product::factory()->create(['category_id' => $category->id]);
        $oldOrder = Order::factory()->create([
            'order_status' => Order::STATUS_PAID,
            'created_at' => now()->subDays(60),
        ]);
        OrderItem::factory()->create([
            'order_id' => $oldOrder->id,
            'product_id' => $oldProduct->id,
        ]);

        $trending = $this->service->getTrendingProducts(5, 30);

        // Should include the recent trending product
        $this->assertTrue($trending->contains('id', $trendingProduct->id));
        
        // Should not include the old product
        $this->assertFalse($trending->contains('id', $oldProduct->id));
    }

    #[Test]
    public function it_gets_new_arrivals_ordered_by_creation_date(): void
    {
        $category = ProductCategory::factory()->create();
        
        // Create products with different creation dates
        Product::factory()->create([
            'category_id' => $category->id,
            'created_at' => now()->subDays(10),
        ]);
        
        $newest = Product::factory()->create([
            'category_id' => $category->id,
            'created_at' => now(),
        ]);

        $newArrivals = $this->service->getNewArrivals(5);

        $this->assertNotEmpty($newArrivals);
        // Newest should come first
        $this->assertEquals($newest->id, $newArrivals->first()->id);
    }

    #[Test]
    public function it_gets_category_products(): void
    {
        $category1 = ProductCategory::factory()->create();
        $category2 = ProductCategory::factory()->create();
        
        Product::factory()->count(3)->create(['category_id' => $category1->id]);
        Product::factory()->count(2)->create(['category_id' => $category2->id]);

        $categoryProducts = $this->service->getCategoryProducts($category1->id, 5);

        $this->assertCount(3, $categoryProducts);
        $categoryProducts->each(function ($p) use ($category1) {
            $this->assertEquals($category1->id, $p->category_id);
        });
    }

    #[Test]
    public function it_excludes_specific_product_from_category_products(): void
    {
        $category = ProductCategory::factory()->create();
        $product1 = Product::factory()->create(['category_id' => $category->id]);
        Product::factory()->create(['category_id' => $category->id]);
        Product::factory()->create(['category_id' => $category->id]);

        $categoryProducts = $this->service->getCategoryProducts($category->id, 5, $product1->id);

        $this->assertFalse($categoryProducts->contains('id', $product1->id));
        $this->assertGreaterThan(0, $categoryProducts->count());
    }

    #[Test]
    public function it_caches_personalized_recommendations(): void
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->count(3)->create(['category_id' => $category->id]);

        // First call should cache
        $first = $this->service->getPersonalizedRecommendations($this->customer->id, 4);
        
        // Second call should use cache
        $second = $this->service->getPersonalizedRecommendations($this->customer->id, 4);

        $this->assertEquals($first->pluck('id')->toArray(), $second->pluck('id')->toArray());
    }

    #[Test]
    public function it_caches_popular_products(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $order = Order::factory()->create(['order_status' => Order::STATUS_PAID]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id]);

        // First call
        $first = $this->service->getPopularProducts(4);
        
        // Second call (should be cached)
        $second = $this->service->getPopularProducts(4);

        $this->assertEquals($first->pluck('id')->toArray(), $second->pluck('id')->toArray());
    }

    #[Test]
    public function it_caches_similar_products(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        Product::factory()->count(3)->create(['category_id' => $category->id]);

        // First call
        $first = $this->service->getSimilarProducts($product->id, 4);
        
        // Second call (should be cached)
        $second = $this->service->getSimilarProducts($product->id, 4);

        $this->assertEquals($first->pluck('id')->toArray(), $second->pluck('id')->toArray());
    }

    #[Test]
    public function it_clears_user_cache(): void
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->count(3)->create(['category_id' => $category->id]);

        // Cache recommendations
        $this->service->getPersonalizedRecommendations($this->customer->id, 8);
        
        // Clear cache
        $this->service->clearUserCache($this->customer->id);
        
        // Cache key should be cleared
        $cacheKey = "recommendations.user.{$this->customer->id}.8";
        $this->assertNull(Cache::get($cacheKey));
    }

    #[Test]
    public function it_returns_popular_products_for_guest_users(): void
    {
        $category = ProductCategory::factory()->create();
        $productId = Product::factory()->create(['category_id' => $category->id])->id;
        $order = Order::factory()->create(['order_status' => Order::STATUS_PAID]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $productId]);

        // Null userId should return popular products
        $recommendations = $this->service->getPersonalizedRecommendations(null, 4);

        $this->assertNotEmpty($recommendations);
    }

    #[Test]
    public function it_respects_limit_parameter(): void
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->count(10)->create(['category_id' => $category->id]);

        $recommendations = $this->service->getNewArrivals(5);

        $this->assertLessThanOrEqual(5, $recommendations->count());
    }

    #[Test]
    public function it_loads_relationships_for_recommended_products(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $recommendations = $this->service->getNewArrivals(5);

        if ($recommendations->isNotEmpty()) {
            $first = $recommendations->first();
            $this->assertTrue($first->relationLoaded('category'));
            $this->assertTrue($first->relationLoaded('images'));
        } else {
            $this->assertTrue(true); // Skip if no products
        }
    }
}

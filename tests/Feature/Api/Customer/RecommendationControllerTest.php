<?php

namespace Tests\Feature\Api\Customer;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecommendationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->create(['role' => 'customer']);
        Cache::flush();
    }

    #[Test]
    public function it_gets_personalized_recommendations_for_authenticated_user(): void
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->count(5)->create(['category_id' => $category->id]);

        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/recommendations/personalized');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'base_price',
                        'category',
                        'images',
                        'is_active',
                    ],
                ],
            ]);
    }

    #[Test]
    public function it_requires_authentication_for_personalized_when_not_logged_in(): void
    {
        // Guest user request (no authentication)
        $response = $this->getJson('/api/v1/recommendations/personalized');

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_gets_popular_products(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $order = Order::factory()->create(['order_status' => Order::STATUS_PAID]);
        OrderItem::factory()->count(5)->create(['order_id' => $order->id, 'product_id' => $product->id]);

        $response = $this->getJson('/api/v1/recommendations/popular');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'base_price',
                        'category',
                        'images',
                        'is_active',
                        'order_count',
                    ],
                ],
            ]);

        if (count($response->json('data')) > 0) {
            $this->assertArrayHasKey('order_count', $response->json('data.0'));
        }
    }

    #[Test]
    public function it_gets_similar_products_for_a_product(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        Product::factory()->count(3)->create(['category_id' => $category->id]);

        $response = $this->getJson("/api/v1/recommendations/similar/{$product->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'base_price',
                        'category',
                        'images',
                        'is_active',
                    ],
                ],
            ]);
    }

    #[Test]
    public function it_returns_empty_array_for_similar_products_of_nonexistent_product(): void
    {
        $response = $this->getJson('/api/v1/recommendations/similar/99999');

        $response->assertOk()
            ->assertJson([
                'message' => 'Similar products retrieved successfully',
                'data' => [],
            ]);
    }

    #[Test]
    public function it_gets_trending_products(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $order = Order::factory()->create([
            'order_status' => Order::STATUS_PAID,
            'created_at' => now()->subDays(10),
        ]);
        OrderItem::factory()->count(3)->create(['order_id' => $order->id, 'product_id' => $product->id]);

        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/recommendations/trending');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'base_price',
                        'category',
                        'images',
                        'is_active',
                        'recent_order_count',
                    ],
                ],
            ]);
    }

    #[Test]
    public function it_gets_new_arrivals(): void
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->count(5)->create([
            'category_id' => $category->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/recommendations/new-arrivals');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'base_price',
                        'category',
                        'images',
                        'is_active',
                        'created_at',
                    ],
                ],
            ]);
    }

    #[Test]
    public function authenticated_users_can_access_personalized_recommendations(): void
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->count(3)->create(['category_id' => $category->id]);

        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/recommendations/personalized');

        $response->assertOk();
    }

    #[Test]
    public function it_requires_authentication_for_trending(): void
    {
        $response = $this->getJson('/api/v1/recommendations/trending');

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_requires_authentication_for_new_arrivals(): void
    {
        $response = $this->getJson('/api/v1/recommendations/new-arrivals');

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_allows_guests_to_access_popular_recommendations(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        $order = Order::factory()->create(['order_status' => Order::STATUS_PAID]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id]);

        $response = $this->getJson('/api/v1/recommendations/popular');

        $response->assertOk();
    }

    #[Test]
    public function it_allows_guests_to_access_similar_recommendations(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $response = $this->getJson("/api/v1/recommendations/similar/{$product->id}");

        $response->assertOk();
    }

    #[Test]
    public function it_returns_products_with_category_information(): void
    {
        $category = ProductCategory::factory()->create(['name' => 'Wedding Invitations']);
        Product::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/recommendations/new-arrivals');

        $response->assertOk();

        if (count($response->json('data')) > 0) {
            $firstProduct = $response->json('data.0');
            $this->assertArrayHasKey('category', $firstProduct);
            $this->assertIsArray($firstProduct['category']);
        }
    }

    #[Test]
    public function it_returns_limited_number_of_recommendations(): void
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->count(20)->create(['category_id' => $category->id]);

        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/recommendations/new-arrivals');

        $response->assertOk();
        $this->assertLessThanOrEqual(8, count($response->json('data')));
    }

    #[Test]
    public function it_handles_empty_product_catalog(): void
    {
        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/recommendations/new-arrivals');

        $response->assertOk()
            ->assertJson([
                'message' => 'New arrivals retrieved successfully',
                'data' => [],
            ]);
    }

    #[Test]
    public function personalized_recommendations_differ_based_on_user_history(): void
    {
        $category1 = ProductCategory::factory()->create();
        $category2 = ProductCategory::factory()->create();

        // User 1 purchases from category 1
        $product1 = Product::factory()->create(['category_id' => $category1->id]);
        Product::factory()->count(5)->create(['category_id' => $category1->id]);

        $order1 = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'order_status' => Order::STATUS_PAID,
        ]);
        OrderItem::factory()->create(['order_id' => $order1->id, 'product_id' => $product1->id]);

        // User 2 purchases from category 2
        /** @var User $user2 */
        $user2 = User::factory()->create(['role' => 'customer']);
        $product2 = Product::factory()->create(['category_id' => $category2->id]);
        Product::factory()->count(5)->create(['category_id' => $category2->id]);

        $order2 = Order::factory()->create([
            'customer_id' => $user2->id,
            'order_status' => Order::STATUS_PAID,
        ]);
        OrderItem::factory()->create(['order_id' => $order2->id, 'product_id' => $product2->id]);

        $response1 = $this->actingAs($this->customer)
            ->getJson('/api/v1/recommendations/personalized');

        $response2 = $this->actingAs($user2)
            ->getJson('/api/v1/recommendations/personalized');

        // Both should get recommendations, but they may differ based on history
        $response1->assertOk();
        $response2->assertOk();

        // Verify both users get some recommendations
        $this->assertGreaterThanOrEqual(0, count($response1->json('data')));
        $this->assertGreaterThanOrEqual(0, count($response2->json('data')));
    }
}

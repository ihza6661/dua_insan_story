<?php

namespace Tests\Feature\Api\Customer;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Customer Review Controller Tests
 *
 * Tests all customer review operations (CRUD, filtering, helpfulness)
 */
class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected User $admin;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->product = Product::factory()->create();
    }

    // ===========================
    // Index Tests (Product Reviews)
    // ===========================

    #[Test]
    public function it_gets_reviews_for_a_product(): void
    {
        Review::factory()->count(5)->approved()->create(['product_id' => $this->product->id]);
        Review::factory()->count(3)->pending()->create(['product_id' => $this->product->id]);
        Review::factory()->count(2)->approved()->create(); // Different product

        $response = $this->getJson("/api/v1/customer/products/{$this->product->id}/reviews");

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'rating',
                        'comment',
                        'customer',
                        'product',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        // Should only show approved reviews (5 out of 8)
        $this->assertCount(5, $response->json('data'));
    }

    #[Test]
    public function it_filters_reviews_by_rating(): void
    {
        Review::factory()->approved()->create(['product_id' => $this->product->id, 'rating' => 5]);
        Review::factory()->approved()->create(['product_id' => $this->product->id, 'rating' => 5]);
        Review::factory()->approved()->create(['product_id' => $this->product->id, 'rating' => 3]);

        $response = $this->getJson("/api/v1/customer/products/{$this->product->id}/reviews?rating=5");

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    #[Test]
    public function it_filters_reviews_with_images(): void
    {
        $reviewWithImage = Review::factory()->approved()->create(['product_id' => $this->product->id]);
        $reviewWithImage->images()->create([
            'image_path' => 'reviews/test.jpg',
            'alt_text' => 'Test',
            'display_order' => 1,
        ]);
        Review::factory()->approved()->create(['product_id' => $this->product->id]); // No images

        $response = $this->getJson("/api/v1/customer/products/{$this->product->id}/reviews?with_images=true");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    #[Test]
    public function it_sorts_reviews_by_latest(): void
    {
        $old = Review::factory()->approved()->create([
            'product_id' => $this->product->id,
            'created_at' => now()->subDays(5),
        ]);
        $new = Review::factory()->approved()->create([
            'product_id' => $this->product->id,
            'created_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/customer/products/{$this->product->id}/reviews?sort_by=latest");

        $response->assertOk();
        $this->assertEquals($new->id, $response->json('data.0.id'));
    }

    // ===========================
    // Rating Summary Tests
    // ===========================

    #[Test]
    public function it_gets_rating_summary_for_product(): void
    {
        Review::factory()->approved()->count(5)->create(['product_id' => $this->product->id, 'rating' => 5]);
        Review::factory()->approved()->count(3)->create(['product_id' => $this->product->id, 'rating' => 4]);
        Review::factory()->approved()->count(2)->create(['product_id' => $this->product->id, 'rating' => 3]);

        $response = $this->getJson("/api/v1/customer/products/{$this->product->id}/reviews/summary");

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'average_rating',
                    'total_reviews',
                    'rating_breakdown',
                ],
            ]);
    }

    // ===========================
    // Show Tests
    // ===========================

    #[Test]
    public function it_shows_a_single_review(): void
    {
        $review = Review::factory()->approved()->create();

        $response = $this->getJson("/api/v1/customer/reviews/{$review->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $review->id)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'rating',
                    'comment',
                    'customer',
                    'product',
                ],
            ]);
    }

    // ===========================
    // Store Tests (Create Review)
    // ===========================

    #[Test]
    public function it_creates_a_review_for_purchased_product(): void
    {
        // Create a fresh orderItem for this specific test
        $newProduct = Product::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'order_status' => 'Completed', // Must be Completed or Delivered to review
        ]);
        $orderItem = OrderItem::factory()->create([
            'product_id' => $newProduct->id,
            'order_id' => $order->id,
        ]);

        // Verify no existing review for this order item
        $this->assertDatabaseMissing('reviews', [
            'order_item_id' => $orderItem->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/reviews', [
                'order_item_id' => $orderItem->id,
                'product_id' => $newProduct->id,
                'rating' => 5,
                'comment' => 'Excellent product!',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.comment', 'Excellent product!');

        $this->assertDatabaseHas('reviews', [
            'order_item_id' => $orderItem->id,
            'customer_id' => $this->customer->id,
            'product_id' => $newProduct->id,
            'rating' => 5,
            'comment' => 'Excellent product!',
        ]);
    }

    #[Test]
    public function it_validates_rating_is_required(): void
    {
        $orderItem = OrderItem::factory()->create();

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/reviews', [
                'order_item_id' => $orderItem->id,
                'product_id' => $this->product->id,
                'comment' => 'Good product',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    #[Test]
    public function it_validates_rating_is_between_1_and_5(): void
    {
        $orderItem = OrderItem::factory()->create();

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/reviews', [
                'order_item_id' => $orderItem->id,
                'product_id' => $this->product->id,
                'rating' => 6,
                'comment' => 'Test',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rating']);
    }

    #[Test]
    public function it_validates_comment_max_length(): void
    {
        $orderItem = OrderItem::factory()->create();
        $orderItem->order->update(['customer_id' => $this->customer->id]);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/reviews', [
                'order_item_id' => $orderItem->id,
                'product_id' => $this->product->id,
                'rating' => 5,
                'comment' => str_repeat('a', 1001), // Exceeds 1000 char limit
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['comment']);
    }

    #[Test]
    public function it_requires_authentication_to_create_review(): void
    {
        $response = $this->postJson('/api/v1/reviews', [
            'product_id' => $this->product->id,
            'rating' => 5,
            'comment' => 'Test',
        ]);

        $response->assertUnauthorized();
    }

    // ===========================
    // Update Tests
    // ===========================

    #[Test]
    public function it_updates_own_review(): void
    {
        $review = Review::factory()->create(['customer_id' => $this->customer->id]);

        $response = $this->actingAs($this->customer)
            ->putJson("/api/v1/reviews/{$review->id}", [
                'rating' => 4,
                'comment' => 'Updated comment',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.rating', 4)
            ->assertJsonPath('data.comment', 'Updated comment');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 4,
            'comment' => 'Updated comment',
        ]);
    }

    #[Test]
    public function it_cannot_update_another_customers_review(): void
    {
        $otherCustomer = User::factory()->create(['role' => 'customer']);
        $review = Review::factory()->create(['customer_id' => $otherCustomer->id]);

        $response = $this->actingAs($this->customer)
            ->putJson("/api/v1/reviews/{$review->id}", [
                'rating' => 1,
                'comment' => 'Bad',
            ]);

        $response->assertStatus(400); // Service throws exception
    }

    // ===========================
    // Delete Tests
    // ===========================

    #[Test]
    public function it_deletes_own_review(): void
    {
        $review = Review::factory()->create(['customer_id' => $this->customer->id]);

        $response = $this->actingAs($this->customer)
            ->deleteJson("/api/v1/reviews/{$review->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Ulasan berhasil dihapus.');

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    #[Test]
    public function it_cannot_delete_another_customers_review(): void
    {
        $otherCustomer = User::factory()->create(['role' => 'customer']);
        $review = Review::factory()->create(['customer_id' => $otherCustomer->id]);

        $response = $this->actingAs($this->customer)
            ->deleteJson("/api/v1/reviews/{$review->id}");

        $response->assertStatus(400);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
        ]);
    }

    // ===========================
    // Mark as Helpful Tests
    // ===========================

    #[Test]
    public function it_marks_review_as_helpful(): void
    {
        $review = Review::factory()->create(['helpful_count' => 5]);

        $response = $this->postJson("/api/v1/customer/reviews/{$review->id}/helpful");

        $response->assertOk()
            ->assertJsonPath('message', 'Ulasan ditandai sebagai membantu.');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'helpful_count' => 6,
        ]);
    }

    // ===========================
    // My Reviews Tests
    // ===========================

    #[Test]
    public function it_gets_customers_own_reviews(): void
    {
        Review::factory()->count(3)->create(['customer_id' => $this->customer->id]);
        Review::factory()->count(2)->create(); // Other customer's reviews

        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/reviews/my');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'rating',
                        'comment',
                        'product',
                    ],
                ],
                'meta',
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    #[Test]
    public function it_requires_authentication_to_get_own_reviews(): void
    {
        $response = $this->getJson('/api/v1/reviews/my');

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_returns_empty_list_when_customer_has_no_reviews(): void
    {
        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/reviews/my');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }
}

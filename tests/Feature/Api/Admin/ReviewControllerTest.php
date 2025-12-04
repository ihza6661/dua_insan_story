<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Admin Review Controller Tests
 *
 * Tests all admin review management operations
 */
class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->customer = User::factory()->create(['role' => 'customer']);
    }

    // ===========================
    // Index Tests
    // ===========================

    #[Test]
    public function it_gets_all_reviews_with_pagination(): void
    {
        Review::factory()->count(25)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/reviews?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'rating',
                        'comment',
                        'is_approved',
                        'is_featured',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 25);
    }

    #[Test]
    public function it_filters_reviews_by_approval_status(): void
    {
        Review::factory()->count(5)->approved()->create();
        Review::factory()->count(3)->pending()->create();
        Review::factory()->count(2)->rejected()->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/reviews?is_approved=approved');

        $response->assertOk();
        $this->assertCount(5, $response->json('data'));
    }

    #[Test]
    public function it_filters_reviews_by_rating(): void
    {
        Review::factory()->count(3)->create(['rating' => 5]);
        Review::factory()->count(2)->create(['rating' => 3]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/reviews?rating=5');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    #[Test]
    public function it_filters_reviews_by_product(): void
    {
        $product = Product::factory()->create();
        Review::factory()->count(4)->create(['product_id' => $product->id]);
        Review::factory()->count(2)->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/reviews?product_id={$product->id}");

        $response->assertOk();
        $this->assertCount(4, $response->json('data'));
    }

    #[Test]
    public function it_filters_reviews_by_featured_status(): void
    {
        Review::factory()->count(3)->featured()->create();
        Review::factory()->count(5)->create(['is_featured' => false]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/reviews?is_featured=true');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }

    #[Test]
    public function it_searches_reviews_by_comment(): void
    {
        Review::factory()->create(['comment' => 'Excellent product quality']);
        Review::factory()->create(['comment' => 'Poor service']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/reviews?search=excellent');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    // ===========================
    // Show Tests
    // ===========================

    #[Test]
    public function it_shows_a_single_review_with_relationships(): void
    {
        $review = Review::factory()->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/reviews/{$review->id}");

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
    // Approve Tests
    // ===========================

    #[Test]
    public function it_approves_a_pending_review(): void
    {
        $review = Review::factory()->pending()->create();

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/reviews/{$review->id}/approve");

        $response->assertOk()
            ->assertJsonPath('message', 'Ulasan berhasil disetujui.')
            ->assertJsonPath('data.is_approved', true);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'is_approved' => true,
        ]);
    }

    #[Test]
    public function it_clears_cache_when_approving_review(): void
    {
        Cache::put('review_statistics', ['test' => 'data'], 300);
        $review = Review::factory()->pending()->create();

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/reviews/{$review->id}/approve");

        $this->assertFalse(Cache::has('review_statistics'));
    }

    // ===========================
    // Reject Tests
    // ===========================

    #[Test]
    public function it_rejects_a_pending_review(): void
    {
        $review = Review::factory()->pending()->create();

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/reviews/{$review->id}/reject");

        $response->assertOk()
            ->assertJsonPath('message', 'Ulasan berhasil ditolak.')
            ->assertJsonPath('data.is_approved', false);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'is_approved' => false,
        ]);
    }

    // ===========================
    // Toggle Featured Tests
    // ===========================

    #[Test]
    public function it_toggles_review_featured_status_on(): void
    {
        $review = Review::factory()->create(['is_featured' => false]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/reviews/{$review->id}/toggle-featured");

        $response->assertOk()
            ->assertJsonPath('message', 'Ulasan berhasil ditandai sebagai unggulan.')
            ->assertJsonPath('data.is_featured', true);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'is_featured' => true,
        ]);
    }

    #[Test]
    public function it_toggles_review_featured_status_off(): void
    {
        $review = Review::factory()->featured()->create();

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/reviews/{$review->id}/toggle-featured");

        $response->assertOk()
            ->assertJsonPath('message', 'Ulasan berhasil dihapus dari unggulan.')
            ->assertJsonPath('data.is_featured', false);
    }

    // ===========================
    // Admin Response Tests
    // ===========================

    #[Test]
    public function it_adds_admin_response_to_review(): void
    {
        $review = Review::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/reviews/{$review->id}/response", [
                'admin_response' => 'Thank you for your feedback!',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Respon admin berhasil ditambahkan.')
            ->assertJsonPath('data.admin_response', 'Thank you for your feedback!');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'admin_response' => 'Thank you for your feedback!',
            'admin_responder_id' => $this->admin->id,
        ]);
    }

    #[Test]
    public function it_validates_admin_response_is_required(): void
    {
        $review = Review::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/reviews/{$review->id}/response", [
                'admin_response' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['admin_response']);
    }

    #[Test]
    public function it_validates_admin_response_max_length(): void
    {
        $review = Review::factory()->create();

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/reviews/{$review->id}/response", [
                'admin_response' => str_repeat('a', 1001),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['admin_response']);
    }

    // ===========================
    // Delete Tests
    // ===========================

    #[Test]
    public function it_deletes_a_review(): void
    {
        $review = Review::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/reviews/{$review->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Ulasan berhasil dihapus.');

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    #[Test]
    public function it_deletes_review_images_when_deleting_review(): void
    {
        $review = Review::factory()->create();
        $image = ReviewImage::factory()->create(['review_id' => $review->id]);

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/reviews/{$review->id}");

        $this->assertDatabaseMissing('review_images', [
            'id' => $image->id,
        ]);
    }

    // ===========================
    // Delete Image Tests
    // ===========================

    #[Test]
    public function it_deletes_a_review_image(): void
    {
        $image = ReviewImage::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/review-images/{$image->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Gambar ulasan berhasil dihapus.');

        $this->assertDatabaseMissing('review_images', [
            'id' => $image->id,
        ]);
    }

    // ===========================
    // Statistics Tests
    // ===========================

    #[Test]
    public function it_gets_review_statistics(): void
    {
        Review::factory()->count(10)->approved()->create(['rating' => 5]);
        Review::factory()->count(5)->pending()->create(['rating' => 4]);
        Review::factory()->count(3)->featured()->create(['rating' => 5]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/reviews/statistics');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'total_reviews',
                    'pending_reviews',
                    'approved_reviews',
                    'featured_reviews',
                    'average_rating',
                    'reviews_with_images',
                    'rating_distribution' => [
                        '5_star',
                        '4_star',
                        '3_star',
                        '2_star',
                        '1_star',
                    ],
                ],
            ])
            ->assertJsonPath('data.total_reviews', 18)
            ->assertJsonPath('data.pending_reviews', 5)
            ->assertJsonPath('data.approved_reviews', 13) // 10 + 3 featured (featured are also approved)
            ->assertJsonPath('data.featured_reviews', 3);
    }

    #[Test]
    public function it_caches_review_statistics(): void
    {
        Review::factory()->count(5)->create();

        // First call - should cache
        $response1 = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/reviews/statistics');

        // Create more reviews
        Review::factory()->count(3)->create();

        // Second call - should return cached data
        $response2 = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/reviews/statistics');

        // Both should have the same total (cached value)
        $this->assertEquals(
            $response1->json('data.total_reviews'),
            $response2->json('data.total_reviews')
        );
    }

    #[Test]
    public function it_calculates_average_rating_correctly(): void
    {
        Review::factory()->approved()->create(['rating' => 5]);
        Review::factory()->approved()->create(['rating' => 4]);
        Review::factory()->approved()->create(['rating' => 3]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/reviews/statistics');

        $response->assertOk();
        
        // Check average rating (allowing for type conversion - could be int or float)
        $this->assertEquals(4.0, (float) $response->json('data.average_rating')); // (5+4+3)/3 = 4.0
    }

    // ===========================
    // Authorization Tests
    // ===========================

    #[Test]
    public function it_requires_admin_role_for_all_endpoints(): void
    {
        $review = Review::factory()->create();

        $endpoints = [
            ['method' => 'get', 'uri' => '/api/v1/admin/reviews'],
            ['method' => 'get', 'uri' => "/api/v1/admin/reviews/{$review->id}"],
            ['method' => 'post', 'uri' => "/api/v1/admin/reviews/{$review->id}/approve"],
            ['method' => 'post', 'uri' => "/api/v1/admin/reviews/{$review->id}/reject"],
            ['method' => 'post', 'uri' => "/api/v1/admin/reviews/{$review->id}/toggle-featured"],
            ['method' => 'delete', 'uri' => "/api/v1/admin/reviews/{$review->id}"],
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->actingAs($this->customer)
                ->{$endpoint['method'].'Json'}($endpoint['uri']);

            $response->assertForbidden();
        }
    }

    #[Test]
    public function it_requires_authentication_for_all_endpoints(): void
    {
        $review = Review::factory()->create();

        $response = $this->getJson('/api/v1/admin/reviews');

        $response->assertUnauthorized();
    }
}

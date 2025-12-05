<?php

namespace Tests\Feature\Api\Customer;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WishlistControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $otherUser;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'customer']);
        $this->otherUser = User::factory()->create(['role' => 'customer']);
        $this->product = Product::factory()->create();
    }

    #[Test]
    public function user_can_view_their_wishlist(): void
    {
        Wishlist::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/wishlist');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'items',
                    'share_token',
                    'shareable_link',
                ],
            ]);

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
        ]);
    }

    #[Test]
    public function user_can_add_product_to_wishlist(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/wishlist', [
                'product_id' => $this->product->id,
            ]);

        $response->assertCreated()
            ->assertJson([
                'message' => 'Product added to wishlist',
            ]);

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
        ]);
    }

    #[Test]
    public function user_cannot_add_duplicate_product_to_wishlist(): void
    {
        // Add product first time
        Wishlist::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
        ]);

        // Try to add again
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/wishlist', [
                'product_id' => $this->product->id,
            ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Product already in wishlist',
            ]);
    }

    #[Test]
    public function user_can_remove_product_from_wishlist(): void
    {
        $wishlist = Wishlist::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/wishlist/{$wishlist->id}");

        $response->assertOk()
            ->assertJson([
                'message' => 'Product removed from wishlist',
            ]);

        $this->assertDatabaseMissing('wishlists', [
            'id' => $wishlist->id,
        ]);
    }

    #[Test]
    public function user_cannot_remove_other_users_wishlist_item(): void
    {
        $wishlist = Wishlist::factory()->create([
            'user_id' => $this->otherUser->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/wishlist/{$wishlist->id}");

        $response->assertNotFound();

        $this->assertDatabaseHas('wishlists', [
            'id' => $wishlist->id,
        ]);
    }

    #[Test]
    public function user_can_check_if_product_is_in_wishlist(): void
    {
        $wishlist = Wishlist::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/wishlist/check/{$this->product->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'in_wishlist',
                    'wishlist_id',
                ],
            ])
            ->assertJson([
                'data' => [
                    'in_wishlist' => true,
                    'wishlist_id' => $wishlist->id,
                ],
            ]);
    }

    #[Test]
    public function user_can_check_if_product_is_not_in_wishlist(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/wishlist/check/{$this->product->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'in_wishlist',
                    'wishlist_id',
                ],
            ])
            ->assertJson([
                'data' => [
                    'in_wishlist' => false,
                    'wishlist_id' => null,
                ],
            ]);
    }

    #[Test]
    public function guest_can_view_shared_wishlist(): void
    {
        $wishlist = Wishlist::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->getJson("/api/v1/wishlist/shared/{$wishlist->share_token}");

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data',
            ]);
    }

    #[Test]
    public function shared_wishlist_returns_404_for_invalid_token(): void
    {
        $response = $this->getJson('/api/v1/wishlist/shared/invalid-token');

        $response->assertNotFound();
    }

    #[Test]
    public function guest_cannot_add_to_wishlist(): void
    {
        $response = $this->postJson('/api/v1/wishlist', [
            'product_id' => $this->product->id,
        ]);

        $response->assertUnauthorized();
    }

    #[Test]
    public function wishlist_items_include_featured_image_structure(): void
    {
        Wishlist::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/wishlist');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'items' => [
                        '*' => [
                            'id',
                            'product' => [
                                'id',
                                'name',
                                'base_price',
                                'slug',
                                'category',
                                'featured_image',
                                'is_active',
                            ],
                            'share_token',
                            'shareable_link',
                            'added_at',
                        ],
                    ],
                    'share_token',
                    'shareable_link',
                ],
            ]);

        // Verify featured_image is null when product has no images
        $data = $response->json('data.items.0');
        $this->assertArrayHasKey('featured_image', $data['product']);
    }

    #[Test]
    public function adding_product_to_wishlist_returns_featured_image(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/wishlist', [
                'product_id' => $this->product->id,
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'product' => [
                        'id',
                        'name',
                        'base_price',
                        'slug',
                        'category',
                        'featured_image',
                    ],
                    'share_token',
                    'shareable_link',
                    'added_at',
                ],
            ]);

        // Verify featured_image key exists
        $data = $response->json('data.product');
        $this->assertArrayHasKey('featured_image', $data);
    }

    #[Test]
    public function shared_wishlist_items_include_featured_image(): void
    {
        $wishlist = Wishlist::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->getJson("/api/v1/wishlist/shared/{$wishlist->share_token}");

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'owner' => [
                        'name',
                    ],
                    'items' => [
                        '*' => [
                            'id',
                            'product' => [
                                'id',
                                'name',
                                'base_price',
                                'slug',
                                'category',
                                'featured_image',
                                'is_active',
                            ],
                            'added_at',
                        ],
                    ],
                ],
            ]);

        // Verify featured_image key exists
        $data = $response->json('data.items.0');
        $this->assertArrayHasKey('featured_image', $data['product']);
    }
}

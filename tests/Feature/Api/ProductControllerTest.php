<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Class ProductControllerTest
 *
 * Feature tests for Product API endpoints.
 */
class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    #[Test]
    public function admin_can_list_all_products(): void
    {
        Product::factory()->count(5)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'base_price',
                        'is_active',
                    ],
                ],
            ]);
    }

    #[Test]
    public function admin_can_create_product(): void
    {
        $category = ProductCategory::factory()->create();

        $productData = [
            'category_id' => $category->id,
            'name' => 'New Product',
            'description' => 'Product Description',
            'base_price' => 150000,
            'weight' => 300,
            'min_order_quantity' => 1,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/products', $productData);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'New Product']);

        $this->assertDatabaseHas('products', ['name' => 'New Product']);
    }

    #[Test]
    public function admin_can_update_product(): void
    {
        $product = Product::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/v1/admin/products/{$product->id}", [
                'category_id' => $product->category_id,
                'name' => 'Updated Name',
                'description' => $product->description,
                'base_price' => $product->base_price,
                'weight' => $product->weight,
                'min_order_quantity' => $product->min_order_quantity,
                'is_active' => $product->is_active,
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Name']);

        $this->assertDatabaseHas('products', ['name' => 'Updated Name']);
    }

    #[Test]
    public function admin_can_delete_product_without_dependencies(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/v1/admin/products/{$product->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    #[Test]
    public function guest_cannot_access_admin_product_endpoints(): void
    {
        $response = $this->getJson('/api/v1/admin/products');

        $response->assertStatus(401);
    }

    #[Test]
    public function customer_can_view_active_products(): void
    {
        Product::factory()->count(3)->create(['is_active' => true]);
        Product::factory()->count(2)->create(['is_active' => false]);

        $response = $this->getJson('/api/v1/customer/products');

        $response->assertStatus(200);
        // Should only return active products
    }
}

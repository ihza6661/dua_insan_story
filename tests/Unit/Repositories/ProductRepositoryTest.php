<?php

namespace Tests\Unit\Repositories;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Repositories\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Class ProductRepositoryTest
 *
 * Unit tests for ProductRepository.
 */
class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected ProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ProductRepository(new Product);
    }

    #[Test]
    public function it_can_create_a_product(): void
    {
        $category = ProductCategory::factory()->create();

        $data = [
            'category_id' => $category->id,
            'name' => 'Test Product',
            'description' => 'Test Description',
            'base_price' => 100000,
            'weight' => 500,
            'min_order_quantity' => 1,
            'is_active' => true,
        ];

        $product = $this->repository->create($data);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('Test Product', $product->name);
        $this->assertDatabaseHas('products', ['name' => 'Test Product']);
    }

    #[Test]
    public function it_can_find_product_by_id(): void
    {
        $product = Product::factory()->create();

        $found = $this->repository->findById($product->id);

        $this->assertNotNull($found);
        $this->assertEquals($product->id, $found->id);
    }

    #[Test]
    public function it_can_update_a_product(): void
    {
        $product = Product::factory()->create(['name' => 'Old Name']);

        $updated = $this->repository->update($product, ['name' => 'New Name']);

        $this->assertEquals('New Name', $updated->name);
        $this->assertDatabaseHas('products', ['name' => 'New Name']);
    }

    #[Test]
    public function it_can_delete_a_product(): void
    {
        $product = Product::factory()->create();

        $result = $this->repository->delete($product);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    #[Test]
    public function it_can_get_paginated_active_products(): void
    {
        Product::factory()->count(15)->create(['is_active' => true]);
        Product::factory()->count(5)->create(['is_active' => false]);

        $paginated = $this->repository->getPaginatedActiveProducts([], 10);

        $this->assertEquals(10, $paginated->count());
        $this->assertEquals(15, $paginated->total());
    }

    #[Test]
    public function it_can_filter_products_by_category(): void
    {
        $category = ProductCategory::factory()->create(['slug' => 'wedding']);
        Product::factory()->count(5)->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        Product::factory()->count(3)->create(['is_active' => true]);

        $filtered = $this->repository->getPaginatedActiveProducts([
            'category_slug' => 'wedding',
        ], 10);

        $this->assertEquals(5, $filtered->total());
    }

    #[Test]
    public function it_can_search_products(): void
    {
        Product::factory()->create(['name' => 'Wedding Invitation', 'is_active' => true]);
        Product::factory()->create(['name' => 'Birthday Card', 'is_active' => true]);
        Product::factory()->create(['description' => 'Special wedding package', 'is_active' => true]);

        $results = $this->repository->getPaginatedActiveProducts([
            'search' => 'wedding',
        ], 10);

        $this->assertEquals(2, $results->total());
    }

    #[Test]
    public function it_checks_product_dependencies(): void
    {
        $product = Product::factory()->create();

        $this->assertFalse($this->repository->hasDependencies($product));

        // Simulate adding order item (would require OrderItem factory)
        // For now, just test the base case
    }
}

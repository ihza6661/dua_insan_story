<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Class ProductServiceTest
 *
 * Unit tests for ProductService.
 */
class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductRepositoryInterface $mockRepository;

    protected ProductService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRepository = Mockery::mock(ProductRepositoryInterface::class);
        $this->service = new ProductService($this->mockRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_create_a_product(): void
    {
        $data = [
            'category_id' => 1,
            'name' => 'Test Product',
            'description' => 'Test Description',
            'base_price' => 100000,
            'weight' => 500,
            'min_order_quantity' => 1,
            'is_active' => true,
        ];

        $product = new Product($data);
        $product->id = 1;

        $this->mockRepository
            ->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn($product);

        $result = $this->service->createProduct($data);

        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals('Test Product', $result->name);
    }

    #[Test]
    public function it_can_update_a_product(): void
    {
        $product = new Product([
            'id' => 1,
            'name' => 'Old Name',
        ]);

        $updateData = ['name' => 'New Name'];
        $updatedProduct = new Product(array_merge($product->toArray(), $updateData));

        $this->mockRepository
            ->shouldReceive('update')
            ->once()
            ->with($product, $updateData)
            ->andReturn($updatedProduct);

        $result = $this->service->updateProduct($product, $updateData);

        $this->assertEquals('New Name', $result->name);
    }

    #[Test]
    public function it_throws_exception_when_deleting_product_with_dependencies(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Produk tidak dapat dihapus karena sudah ada dalam pesanan atau keranjang belanja pelanggan.');

        $product = Product::factory()->create();

        $this->mockRepository
            ->shouldReceive('hasDependencies')
            ->once()
            ->with($product)
            ->andReturn(true);

        $this->service->deleteProduct($product);
    }

    #[Test]
    public function it_can_get_paginated_active_products(): void
    {
        $filters = [
            'search' => 'wedding',
            'category_slug' => null,
            'min_price' => null,
            'max_price' => null,
            'sort' => 'latest',
        ];

        $products = Product::factory()->count(5)->make();
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator($products, 5, 10);

        $this->mockRepository
            ->shouldReceive('getPaginatedActiveProducts')
            ->once()
            ->with($filters, 10)
            ->andReturn($paginator);

        $result = $this->service->getPaginatedActiveProducts('wedding');

        $this->assertNotNull($result);
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
    }
}

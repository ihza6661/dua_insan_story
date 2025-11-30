<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Class ProductModelTest
 *
 * Unit tests for Product model scopes.
 */
class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_active_scope(): void
    {
        Product::factory()->count(3)->create(['is_active' => true]);
        Product::factory()->count(2)->create(['is_active' => false]);

        $activeProducts = Product::active()->get();

        $this->assertCount(3, $activeProducts);
        $this->assertTrue($activeProducts->every(fn ($p) => $p->is_active === true));
    }

    #[Test]
    public function it_has_inactive_scope(): void
    {
        Product::factory()->count(3)->create(['is_active' => true]);
        Product::factory()->count(2)->create(['is_active' => false]);

        $inactiveProducts = Product::inactive()->get();

        $this->assertCount(2, $inactiveProducts);
        $this->assertTrue($inactiveProducts->every(fn ($p) => $p->is_active === false));
    }

    #[Test]
    public function it_has_by_category_scope(): void
    {
        $category = ProductCategory::factory()->create(['slug' => 'wedding']);
        $otherCategory = ProductCategory::factory()->create(['slug' => 'birthday']);

        Product::factory()->count(3)->create(['category_id' => $category->id]);
        Product::factory()->count(2)->create(['category_id' => $otherCategory->id]);

        $weddingProducts = Product::byCategory('wedding')->get();

        $this->assertCount(3, $weddingProducts);
    }

    #[Test]
    public function it_has_search_scope(): void
    {
        Product::factory()->create(['name' => 'Wedding Invitation Card']);
        Product::factory()->create(['name' => 'Birthday Card']);
        Product::factory()->create(['description' => 'Special wedding package']);

        $results = Product::search('wedding')->get();

        $this->assertCount(2, $results);
    }

    #[Test]
    public function it_has_latest_scope(): void
    {
        $old = Product::factory()->create(['created_at' => now()->subDays(5)]);
        $new = Product::factory()->create(['created_at' => now()->subDays(1)]);
        $newest = Product::factory()->create(['created_at' => now()]);

        $products = Product::latest()->get();

        $this->assertEquals($newest->id, $products->first()->id);
        $this->assertEquals($old->id, $products->last()->id);
    }

    #[Test]
    public function it_can_chain_scopes(): void
    {
        $category = ProductCategory::factory()->create(['slug' => 'wedding']);

        Product::factory()->create([
            'name' => 'Wedding Card',
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'name' => 'Wedding Album',
            'category_id' => $category->id,
            'is_active' => false,
        ]);

        $results = Product::active()
            ->byCategory('wedding')
            ->search('card')
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Wedding Card', $results->first()->name);
    }

    #[Test]
    public function it_casts_fields_correctly(): void
    {
        $product = Product::factory()->create([
            'is_active' => '1',
            'weight' => '500',
            'base_price' => '100000',
            'min_order_quantity' => '10',
        ]);

        $this->assertIsBool($product->is_active);
        $this->assertIsInt($product->weight);
        $this->assertIsFloat($product->base_price);
        $this->assertIsInt($product->min_order_quantity);
    }
}

<?php

namespace Tests\Unit\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\OrderCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Class OrderCreationServiceTest
 *
 * Unit tests for OrderCreationService.
 */
class OrderCreationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderCreationService $service;

    protected OrderRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(OrderRepositoryInterface::class);
        $this->service = new OrderCreationService($this->repository);
    }

    #[Test]
    public function it_can_create_order_from_cart(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->hasItems(2)->create(['user_id' => $user->id]);

        $order = $this->service->createOrderFromCart(
            customerId: $user->id,
            cart: $cart,
            shippingCost: 50000,
            shippingAddress: 'Test Address',
            shippingMethod: 'JNE',
            shippingService: 'REG',
            courier: 'jne'
        );

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($user->id, $order->customer_id);
        $this->assertEquals(50000, $order->shipping_cost);
        $this->assertDatabaseHas('orders', ['customer_id' => $user->id]);
    }

    #[Test]
    public function it_generates_unique_order_number(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->hasItems(1)->create(['user_id' => $user->id]);

        $order1 = $this->service->createOrderFromCart(
            customerId: $user->id,
            cart: $cart,
            shippingCost: 50000,
            shippingAddress: 'Test Address',
            shippingMethod: 'JNE'
        );

        $order2 = $this->service->createOrderFromCart(
            customerId: $user->id,
            cart: $cart,
            shippingCost: 50000,
            shippingAddress: 'Test Address',
            shippingMethod: 'JNE'
        );

        $this->assertNotEquals($order1->order_number, $order2->order_number);
        $this->assertStringStartsWith('INV-', $order1->order_number);
        $this->assertStringStartsWith('INV-', $order2->order_number);
    }

    #[Test]
    public function it_calculates_total_amount_correctly(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        $product1 = \App\Models\Product::factory()->create();
        $product2 = \App\Models\Product::factory()->create();

        // Create cart items with known prices
        $cart->items()->create([
            'product_id' => $product1->id,
            'product_variant_id' => null,
            'quantity' => 2,
            'unit_price' => 100000,
        ]);

        $cart->items()->create([
            'product_id' => $product2->id,
            'product_variant_id' => null,
            'quantity' => 1,
            'unit_price' => 150000,
        ]);

        $cart->load('items');

        $order = $this->service->createOrderFromCart(
            customerId: $user->id,
            cart: $cart,
            shippingCost: 50000,
            shippingAddress: 'Test Address',
            shippingMethod: 'JNE'
        );

        // Expected: (2 * 100000) + (1 * 150000) + 50000 = 400000
        $this->assertEquals(400000, $order->total_amount);
    }

    #[Test]
    public function it_can_create_order_items_from_cart(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->hasItems(3)->create(['user_id' => $user->id]);
        $order = Order::factory()->create(['customer_id' => $user->id]);

        $this->service->createOrderItemsFromCart($order, $cart->fresh(['items']));

        $this->assertCount(3, $order->fresh()->items);
    }

    #[Test]
    public function it_creates_order_item_meta_from_customization(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        $product = \App\Models\Product::factory()->create();

        $cartItem = $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'quantity' => 1,
            'unit_price' => 100000,
            'customization_details' => [
                'options' => [
                    ['name' => 'Color', 'value' => 'Red'],
                    ['name' => 'Size', 'value' => 'Large'],
                ],
            ],
        ]);

        $order = Order::factory()->create(['customer_id' => $user->id]);
        $cart->load('items');

        $this->service->createOrderItemsFromCart($order, $cart);

        $orderItem = $order->fresh()->items()->first();
        $this->assertCount(2, $orderItem->meta);
        $this->assertEquals('Color', $orderItem->meta->first()->meta_key);
        $this->assertEquals('Red', $orderItem->meta->first()->meta_value);
    }
}

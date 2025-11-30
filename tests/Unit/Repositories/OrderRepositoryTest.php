<?php

namespace Tests\Unit\Repositories;

use App\Models\Order;
use App\Models\User;
use App\Repositories\OrderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Class OrderRepositoryTest
 *
 * Unit tests for OrderRepository.
 */
class OrderRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected OrderRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new OrderRepository(new Order);
    }

    #[Test]
    public function it_can_create_an_order(): void
    {
        $user = User::factory()->create();

        $data = [
            'customer_id' => $user->id,
            'order_number' => 'INV-TEST-001',
            'total_amount' => 500000,
            'shipping_address' => 'Test Address',
            'shipping_cost' => 50000,
            'shipping_method' => 'JNE',
        ];

        $order = $this->repository->create($data);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('INV-TEST-001', $order->order_number);
        $this->assertDatabaseHas('orders', ['order_number' => 'INV-TEST-001']);
    }

    #[Test]
    public function it_can_find_order_by_id(): void
    {
        $order = Order::factory()->create();

        $found = $this->repository->findById($order->id);

        $this->assertNotNull($found);
        $this->assertEquals($order->id, $found->id);
    }

    #[Test]
    public function it_returns_null_when_order_not_found(): void
    {
        $found = $this->repository->findById(99999);

        $this->assertNull($found);
    }

    #[Test]
    public function it_can_update_order(): void
    {
        $order = Order::factory()->create(['order_number' => 'OLD-001']);

        $updated = $this->repository->update($order, ['order_number' => 'NEW-001']);

        $this->assertEquals('NEW-001', $updated->order_number);
        $this->assertDatabaseHas('orders', ['order_number' => 'NEW-001']);
    }

    #[Test]
    public function it_can_get_orders_by_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Order::factory()->count(3)->create(['customer_id' => $user->id]);
        Order::factory()->count(2)->create(['customer_id' => $otherUser->id]);

        $orders = $this->repository->getOrdersByUser($user);

        $this->assertCount(3, $orders);
        $this->assertTrue($orders->every(fn ($order) => $order->customer_id === $user->id));
    }

    #[Test]
    public function it_can_find_order_by_id_for_user(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['customer_id' => $user->id]);

        $found = $this->repository->findOrderByIdForUser($user, $order->id);

        $this->assertNotNull($found);
        $this->assertEquals($order->id, $found->id);
    }

    #[Test]
    public function it_returns_null_when_order_doesnt_belong_to_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = Order::factory()->create(['customer_id' => $otherUser->id]);

        $found = $this->repository->findOrderByIdForUser($user, $order->id);

        $this->assertNull($found);
    }

    #[Test]
    public function it_can_update_order_status(): void
    {
        $order = Order::factory()->create(['order_status' => 'Pending Payment']);

        $updated = $this->repository->updateStatus($order, 'Paid');

        $this->assertEquals('Paid', $updated->order_status);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'Paid',
        ]);
    }

    #[Test]
    public function it_can_get_latest_orders(): void
    {
        Order::factory()->count(15)->create();

        $orders = $this->repository->getLatestOrders(10);

        $this->assertCount(10, $orders);
        // Verify they're ordered by latest
        $this->assertTrue($orders->first()->created_at >= $orders->last()->created_at);
    }

    #[Test]
    public function it_can_get_all_orders_with_relations(): void
    {
        $user = User::factory()->create();
        Order::factory()->count(5)->create(['customer_id' => $user->id]);

        $orders = $this->repository->all(['customer']);

        $this->assertCount(5, $orders);
        // Verify customer relationship is loaded
        $this->assertTrue($orders->first()->relationLoaded('customer'));
    }
}

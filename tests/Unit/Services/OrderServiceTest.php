<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\User;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Class OrderServiceTest
 *
 * Unit tests for OrderService.
 */
class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderRepositoryInterface $mockRepository;

    protected OrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->service = new OrderService($this->mockRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_get_orders_by_user(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $orders = new Collection([
            Order::factory()->make(),
            Order::factory()->make(),
        ]);

        $expectedRelations = [
            'items.product.variants.images',
            'items.product.variants',
            'items.variant.options',
            'invitationDetail',
            'payments',
        ];

        $this->mockRepository
            ->shouldReceive('getOrdersByUser')
            ->once()
            ->with($user, $expectedRelations)
            ->andReturn($orders);

        $result = $this->service->getOrdersByUser($user);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(Collection::class, $result);
    }

    #[Test]
    public function it_can_get_order_by_id_for_user(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $order = Order::factory()->make(['id' => 1]);

        $this->mockRepository
            ->shouldReceive('findOrderByIdForUser')
            ->once()
            ->with($user, 1, Mockery::type('array'))
            ->andReturn($order);

        $result = $this->service->getOrderByIdForUser($user, 1);

        $this->assertNotNull($result);
        $this->assertEquals(1, $result->id);
    }

    #[Test]
    public function it_returns_null_when_order_not_found_for_user(): void
    {
        $user = User::factory()->make(['id' => 1]);

        $this->mockRepository
            ->shouldReceive('findOrderByIdForUser')
            ->once()
            ->with($user, 999, Mockery::type('array'))
            ->andReturn(null);

        $result = $this->service->getOrderByIdForUser($user, 999);

        $this->assertNull($result);
    }

    #[Test]
    public function it_can_update_order_status(): void
    {
        $order = Order::factory()->make(['id' => 1, 'order_status' => 'pending']);
        $updatedOrder = Order::factory()->make(['id' => 1, 'order_status' => 'paid']);

        $this->mockRepository
            ->shouldReceive('updateStatus')
            ->once()
            ->with($order, 'paid')
            ->andReturn($updatedOrder);

        $result = $this->service->updateOrderStatus($order, 'paid');

        $this->assertEquals('paid', $result->order_status);
    }

    #[Test]
    public function it_can_get_all_orders(): void
    {
        $orders = new Collection([
            Order::factory()->make(),
            Order::factory()->make(),
            Order::factory()->make(),
        ]);

        $this->mockRepository
            ->shouldReceive('all')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn($orders);

        $result = $this->service->getAllOrders();

        $this->assertCount(3, $result);
    }

    #[Test]
    public function it_can_get_order_by_id(): void
    {
        $order = Order::factory()->make(['id' => 1]);

        $this->mockRepository
            ->shouldReceive('findByIdOrFail')
            ->once()
            ->with(1, Mockery::type('array'))
            ->andReturn($order);

        $result = $this->service->getOrderById(1);

        $this->assertEquals(1, $result->id);
    }
}

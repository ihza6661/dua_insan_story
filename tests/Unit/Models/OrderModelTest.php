<?php

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Class OrderModelTest
 *
 * Unit tests for Order model scopes and accessors.
 */
class OrderModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_has_pending_scope(): void
    {
        Order::factory()->create(['order_status' => 'Pending Payment']);
        Order::factory()->create(['order_status' => 'Paid']);
        Order::factory()->create(['order_status' => 'Pending Payment']);

        $pendingOrders = Order::pending()->get();

        $this->assertCount(2, $pendingOrders);
        $this->assertTrue($pendingOrders->every(fn ($o) => $o->order_status === 'Pending Payment'));
    }

    #[Test]
    public function it_has_paid_scope(): void
    {
        Order::factory()->create(['order_status' => 'Paid']);
        Order::factory()->create(['order_status' => 'Pending Payment']);
        Order::factory()->create(['order_status' => 'Paid']);

        $paidOrders = Order::paid()->get();

        $this->assertCount(2, $paidOrders);
        $this->assertTrue($paidOrders->every(fn ($o) => $o->order_status === 'Paid'));
    }

    #[Test]
    public function it_has_completed_scope(): void
    {
        Order::factory()->create(['order_status' => 'Completed']);
        Order::factory()->create(['order_status' => 'Paid']);

        $completedOrders = Order::completed()->get();

        $this->assertCount(1, $completedOrders);
        $this->assertEquals('Completed', $completedOrders->first()->order_status);
    }

    #[Test]
    public function it_has_by_customer_scope(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Order::factory()->count(3)->create(['customer_id' => $user1->id]);
        Order::factory()->count(2)->create(['customer_id' => $user2->id]);

        $user1Orders = Order::byCustomer($user1->id)->get();

        $this->assertCount(3, $user1Orders);
        $this->assertTrue($user1Orders->every(fn ($o) => $o->customer_id === $user1->id));
    }

    #[Test]
    public function it_calculates_remaining_balance_without_n_plus_one(): void
    {
        $order = Order::factory()->create(['total_amount' => 1000000]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 500000,
            'status' => 'paid',
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 200000,
            'status' => 'paid',
        ]);

        // Load with optimized scope
        $optimizedOrder = Order::withPaymentTotals()->find($order->id);

        $this->assertEquals(300000, $optimizedOrder->remaining_balance);
    }

    #[Test]
    public function it_calculates_amount_paid_correctly(): void
    {
        $order = Order::factory()->create(['total_amount' => 1000000]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 500000,
            'status' => 'paid',
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 200000,
            'status' => 'pending', // This should not be counted
        ]);

        $optimizedOrder = Order::withPaymentTotals()->find($order->id);

        $this->assertEquals(500000, $optimizedOrder->amount_paid);
    }

    #[Test]
    public function it_protects_critical_fields_from_mass_assignment(): void
    {
        $data = [
            'customer_id' => 1,
            'order_number' => 'INV-001',
            'total_amount' => 100000,
            'order_status' => 'completed', // Should be guarded
            'payment_status' => 'paid', // Should be guarded
        ];

        $order = new Order;
        $order->fill($data);

        // Critical fields should not be filled
        $this->assertNull($order->order_status);
        $this->assertNull($order->payment_status);

        // Other fields should be filled
        $this->assertEquals(1, $order->customer_id);
        $this->assertEquals('INV-001', $order->order_number);
    }

    #[Test]
    public function it_casts_amounts_to_float(): void
    {
        $order = Order::factory()->create([
            'total_amount' => '1000000',
            'shipping_cost' => '50000',
        ]);

        $this->assertIsFloat($order->total_amount);
        $this->assertIsFloat($order->shipping_cost);
        $this->assertEquals(1000000.0, $order->total_amount);
        $this->assertEquals(50000.0, $order->shipping_cost);
    }
}

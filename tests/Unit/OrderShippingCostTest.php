<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderShippingCostTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_accepts_shipping_related_fields(): void
    {
        $user = User::factory()->create();

        $order = Order::create([
            'customer_id' => $user->id,
            'order_number' => 'INV-' . Str::uuid(),
            'total_amount' => 250000,
            'shipping_address' => 'Jl. Test No. 123, Jakarta',
            'order_status' => 'Pending Payment',
            'payment_status' => 'pending',
            'shipping_cost' => 15000,
            'shipping_service' => 'JNE REG',
            'courier' => 'jne',
            'snap_token' => 'token-123',
        ]);

        $this->assertSame(15000.0, $order->shipping_cost);
        $this->assertSame('JNE REG', $order->shipping_service);
        $this->assertSame('jne', $order->courier);
        $this->assertSame('token-123', $order->snap_token);
    }
}

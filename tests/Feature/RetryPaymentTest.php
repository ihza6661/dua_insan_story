<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class RetryPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_retry_payment_when_order_is_pending_or_failed(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'order_status' => 'Failed',
        ]);
        $order->payment_status = 'failed';
        $order->save();

        $payment = $order->payments()->create([
            'transaction_id' => (string) Str::uuid(),
            'amount' => 100_000,
            'status' => 'failed',
            'payment_type' => 'full',
        ]);

        Sanctum::actingAs($user);

        $midtransMock = Mockery::mock(MidtransService::class);
        $midtransMock->shouldReceive('createTransactionToken')
            ->once()
            ->withArgs(function ($orderArg, $paymentArg) use ($order, $payment) {
                return $orderArg->is($order) && $paymentArg->id === $payment->id;
            })
            ->andReturn('new-snap-token');

        $this->app->instance(MidtransService::class, $midtransMock);

    $response = $this->postJson("/api/v1/orders/{$order->id}/retry-payment");

        $response->assertOk()
            ->assertJson([
                'message' => 'Payment token regenerated. Please complete the payment.',
                'snap_token' => 'new-snap-token',
            ]);

        $order->refresh();
        $payment->refresh();

        $this->assertSame('Pending Payment', $order->order_status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('new-snap-token', $payment->snap_token);
    }

    public function test_retry_payment_rejected_for_ineligible_statuses(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'order_status' => 'Completed',
        ]);
        $order->payment_status = 'paid';
        $order->save();

        $order->payments()->create([
            'transaction_id' => (string) Str::uuid(),
            'amount' => 100_000,
            'status' => 'paid',
            'payment_type' => 'full',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/orders/{$order->id}/retry-payment");

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'This order cannot be paid for.',
            ]);
    }

    public function test_user_can_retry_payment_after_order_was_cancelled(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'order_status' => 'Cancelled',
        ]);
        $order->payment_status = 'cancelled';
        $order->save();

        $payment = $order->payments()->create([
            'transaction_id' => (string) Str::uuid(),
            'amount' => 150_000,
            'status' => 'cancelled',
            'payment_type' => 'full',
        ]);

        Sanctum::actingAs($user);

        $midtransMock = Mockery::mock(MidtransService::class);
        $midtransMock->shouldReceive('createTransactionToken')
            ->once()
            ->withArgs(function ($orderArg, $paymentArg) use ($order, $payment) {
                return $orderArg->is($order) && $paymentArg->id === $payment->id;
            })
            ->andReturn('cancelled-retry-token');

        $this->app->instance(MidtransService::class, $midtransMock);

        $response = $this->postJson("/api/v1/orders/{$order->id}/retry-payment");

        $response->assertOk()
            ->assertJson([
                'message' => 'Payment token regenerated. Please complete the payment.',
                'snap_token' => 'cancelled-retry-token',
            ]);

        $order->refresh();
        $payment->refresh();

        $this->assertSame('Pending Payment', $order->order_status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('cancelled-retry-token', $payment->snap_token);
    }
}

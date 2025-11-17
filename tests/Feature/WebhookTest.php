<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $user->id,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
        ]);

        return [$order, $payment];
    }

    private function createPayload($order, $payment, $transactionStatus)
    {
        $payload = [
            'order_id' => $payment->transaction_id,
            'status_code' => '200',
            'gross_amount' => $order->total_amount . '.00',
            'transaction_status' => $transactionStatus,
            'fraud_status' => 'accept',
        ];
        $payload['signature_key'] = hash('sha512', $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . config('midtrans.server_key'));

        return $payload;
    }

    public function test_webhook_handles_settlement()
    {
        [$order, $payment] = $this->createOrder();
        $payload = $this->createPayload($order, $payment, 'settlement');

        $response = $this->postJson('/api/v1/webhook/midtrans', $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'Paid',
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'paid',
        ]);
    }

    public function test_webhook_handles_pending()
    {
        [$order, $payment] = $this->createOrder();
        $payload = $this->createPayload($order, $payment, 'pending');

        $response = $this->postJson('/api/v1/webhook/midtrans', $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'Pending Payment',
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_webhook_handles_deny()
    {
        [$order, $payment] = $this->createOrder();
        $payload = $this->createPayload($order, $payment, 'deny');

        $response = $this->postJson('/api/v1/webhook/midtrans', $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'Failed',
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'failed',
        ]);
    }

    public function test_webhook_handles_cancel()
    {
        [$order, $payment] = $this->createOrder();
        $payload = $this->createPayload($order, $payment, 'cancel');

        $response = $this->postJson('/api/v1/webhook/midtrans', $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'Cancelled',
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_webhook_handles_expire()
    {
        [$order, $payment] = $this->createOrder();
        $payload = $this->createPayload($order, $payment, 'expire');

        $response = $this->postJson('/api/v1/webhook/midtrans', $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'Cancelled',
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_webhook_handles_refund()
    {
        [$order, $payment] = $this->createOrder();
        $payload = $this->createPayload($order, $payment, 'refund');

        $response = $this->postJson('/api/v1/webhook/midtrans', $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'Refunded',
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'refunded',
        ]);
    }

    public function test_webhook_handles_invalid_signature()
    {
        [$order, $payment] = $this->createOrder();
        $payload = $this->createPayload($order, $payment, 'settlement');
        $payload['signature_key'] = 'invalid-signature';

        $response = $this->postJson('/api/v1/webhook/midtrans', $payload);

        $response->assertStatus(400);
        $response->assertJson(['status' => 'error', 'message' => 'Invalid signature']);
    }
}

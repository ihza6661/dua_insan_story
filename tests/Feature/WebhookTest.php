<?php

namespace Tests\Feature;

use App\Models\DigitalInvitation;
use App\Models\InvitationTemplate;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
            'gross_amount' => $order->total_amount.'.00',
            'transaction_status' => $transactionStatus,
            'fraud_status' => 'accept',
        ];
        $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].config('midtrans.server_key'));

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

    public function test_webhook_auto_creates_and_activates_digital_invitation()
    {
        // Create user
        $user = User::factory()->create();

        // Create digital product with template
        $category = ProductCategory::factory()->create(['name' => 'Digital Invitations']);
        $template = InvitationTemplate::factory()->create([
            'name' => 'Sakeenah Islamic',
            'slug' => 'sakeenah',
            'is_active' => true,
        ]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'product_type' => 'digital',
            'template_id' => $template->id,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 150000,
        ]);

        // Create order with digital product
        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'order_status' => 'Pending Payment',
            'total_amount' => 150000,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 150000,
            'sub_total' => 150000,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 150000,
            'payment_type' => 'full',
            'status' => 'pending',
        ]);

        // Update transaction_id to match expected format (payment_id-timestamp)
        $payment->update(['transaction_id' => $payment->id.'-'.time()]);

        // Trigger webhook with settlement
        $payload = $this->createPayload($order, $payment, 'settlement');
        $response = $this->postJson('/api/v1/webhook/midtrans', $payload);

        $response->assertStatus(200);

        // Verify order is paid
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'Paid',
        ]);

        // Verify payment status updated
        $payment->refresh();
        $this->assertEquals('paid', $payment->status);

        // Verify digital invitation was created
        $invitation = DigitalInvitation::where('order_id', $order->id)->first();
        $this->assertNotNull($invitation, 'Digital invitation should be auto-created');

        // Verify invitation is ACTIVE (not draft)
        $this->assertEquals(DigitalInvitation::STATUS_ACTIVE, $invitation->status);
        $this->assertNotNull($invitation->activated_at);
        $this->assertNotNull($invitation->expires_at);

        // Verify expiration is set to 12 months
        $expectedExpiry = now()->addMonths(12);
        $this->assertTrue(
            $invitation->expires_at->diffInMinutes($expectedExpiry) < 2,
            'Expiration should be set to 12 months from now'
        );

        // Verify invitation data record exists
        $this->assertNotNull($invitation->data);

        // Verify template usage count incremented
        $this->assertEquals(1, $template->fresh()->usage_count);
    }

    public function test_webhook_does_not_create_invitation_for_physical_products()
    {
        // Create user
        $user = User::factory()->create();

        // Create physical product (no template)
        $category = ProductCategory::factory()->create(['name' => 'Physical Invitations']);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'product_type' => 'physical',
            'template_id' => null,
        ]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 50000,
        ]);

        // Create order with physical product
        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'order_status' => 'Pending Payment',
            'total_amount' => 50000,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 50000,
            'sub_total' => 50000,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 50000,
            'payment_type' => 'full',
            'status' => 'pending',
        ]);

        // Update transaction_id to match expected format (payment_id-timestamp)
        $payment->update(['transaction_id' => $payment->id.'-'.time()]);

        // Trigger webhook with settlement
        $payload = $this->createPayload($order, $payment, 'settlement');
        $response = $this->postJson('/api/v1/webhook/midtrans', $payload);

        $response->assertStatus(200);

        // Verify order is paid
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'Paid',
        ]);

        // Verify NO digital invitation was created
        $invitationCount = DigitalInvitation::where('order_id', $order->id)->count();
        $this->assertEquals(0, $invitationCount, 'No invitation should be created for physical products');
    }
}

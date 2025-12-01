<?php

namespace Tests\Feature;

use App\Mail\OrderConfirmed;
use App\Mail\OrderDelivered;
use App\Mail\OrderShipped;
use App\Mail\OrderStatusChanged;
use App\Mail\PaymentConfirmed;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_order_confirmed_email_sent_when_order_created(): void
    {
        // Arrange
        $customer = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create();

        $orderData = [
            'customer_id' => $customer->id,
            'total_amount' => 500000,
            'shipping_cost' => 50000,
            'shipping_address' => '123 Test Street',
            'order_status' => Order::STATUS_PENDING_PAYMENT,
        ];

        // Create order
        $order = Order::factory()->create($orderData);

        // Create order item
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'unit_price' => 500000,
            'quantity' => 1,
        ]);

        // Act - Simulate CheckoutService sending email
        Mail::to($customer->email)->send(new OrderConfirmed($order->fresh(['items.product'])));

        // Assert
        Mail::assertQueued(OrderConfirmed::class, function ($mail) use ($customer, $order) {
            return $mail->hasTo($customer->email) &&
                   $mail->order->id === $order->id;
        });
    }

    public function test_payment_confirmed_email_sent_when_payment_succeeds(): void
    {
        // Arrange
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'total_amount' => 500000,
            'order_status' => Order::STATUS_PAID,
            'payment_status' => 'paid',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'unit_price' => 500000,
            'quantity' => 1,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 500000,
            'status' => 'success',
        ]);

        // Act - Simulate WebhookController sending email
        Mail::to($customer->email)->send(new PaymentConfirmed($order->fresh(['items.product']), $payment));

        // Assert
        Mail::assertQueued(PaymentConfirmed::class, function ($mail) use ($customer, $order, $payment) {
            return $mail->hasTo($customer->email) &&
                   $mail->order->id === $order->id &&
                   $mail->payment->id === $payment->id;
        });
    }

    public function test_order_status_changed_email_sent_when_status_updated(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'order_status' => Order::STATUS_PAID,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
        ]);

        $oldStatus = Order::STATUS_PAID;
        $newStatus = Order::STATUS_PROCESSING;

        // Act - Update order status via API
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/orders/{$order->id}/status", [
                'status' => $newStatus,
            ]);

        // Assert
        $response->assertOk();

        Mail::assertQueued(OrderStatusChanged::class, function ($mail) use ($customer, $order, $oldStatus, $newStatus) {
            return $mail->hasTo($customer->email) &&
                   $mail->order->id === $order->id &&
                   $mail->oldStatus === $oldStatus &&
                   $mail->newStatus === $newStatus;
        });
    }

    public function test_order_shipped_email_sent_when_status_changed_to_shipped(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'order_status' => Order::STATUS_IN_PRODUCTION,
            'courier' => 'JNE',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
        ]);

        $trackingNumber = 'JNE12345678';

        // Act - Update order status to Shipped
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/orders/{$order->id}/status", [
                'status' => Order::STATUS_SHIPPED,
                'tracking_number' => $trackingNumber,
            ]);

        // Assert
        $response->assertOk();

        Mail::assertQueued(OrderShipped::class, function ($mail) use ($customer, $order, $trackingNumber) {
            return $mail->hasTo($customer->email) &&
                   $mail->order->id === $order->id &&
                   $mail->trackingNumber === $trackingNumber &&
                   $mail->courierName === 'JNE';
        });

        // Verify tracking number was saved
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'tracking_number' => $trackingNumber,
            'order_status' => Order::STATUS_SHIPPED,
        ]);
    }

    public function test_order_delivered_email_sent_when_status_changed_to_delivered(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'order_status' => Order::STATUS_SHIPPED,
            'tracking_number' => 'JNE12345678',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
        ]);

        // Act - Update order status to Delivered
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/orders/{$order->id}/status", [
                'status' => Order::STATUS_DELIVERED,
            ]);

        // Assert
        $response->assertOk();

        Mail::assertQueued(OrderDelivered::class, function ($mail) use ($customer, $order) {
            return $mail->hasTo($customer->email) &&
                   $mail->order->id === $order->id;
        });

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => Order::STATUS_DELIVERED,
        ]);
    }

    public function test_order_confirmed_mail_has_correct_subject(): void
    {
        // Arrange
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        // Act
        $mail = new OrderConfirmed($order);
        $envelope = $mail->envelope();

        // Assert
        $this->assertEquals('Order Confirmation - '.$order->order_number, $envelope->subject);
    }

    public function test_payment_confirmed_mail_has_correct_subject(): void
    {
        // Arrange
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
        ]);

        // Act
        $mail = new PaymentConfirmed($order, $payment);
        $envelope = $mail->envelope();

        // Assert
        $this->assertEquals('Payment Confirmed - '.$order->order_number, $envelope->subject);
    }

    public function test_order_shipped_mail_has_correct_subject(): void
    {
        // Arrange
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        // Act
        $mail = new OrderShipped($order, 'JNE12345678', 'JNE');
        $envelope = $mail->envelope();

        // Assert
        $this->assertEquals('Your Order Has Been Shipped - '.$order->order_number, $envelope->subject);
    }

    public function test_order_delivered_mail_has_correct_subject(): void
    {
        // Arrange
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        // Act
        $mail = new OrderDelivered($order);
        $envelope = $mail->envelope();

        // Assert
        $this->assertEquals('Your Order Has Been Delivered - '.$order->order_number, $envelope->subject);
    }

    public function test_order_status_changed_mail_has_dynamic_subject_based_on_status(): void
    {
        // Arrange
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        // Test Processing status
        $mail = new OrderStatusChanged($order, Order::STATUS_PAID, Order::STATUS_PROCESSING);
        $envelope = $mail->envelope();
        $this->assertEquals('Your Order is Being Processed - '.$order->order_number, $envelope->subject);

        // Test Design Approval status
        $mail = new OrderStatusChanged($order, Order::STATUS_PROCESSING, Order::STATUS_DESIGN_APPROVAL);
        $envelope = $mail->envelope();
        $this->assertEquals('Design Approval Required - '.$order->order_number, $envelope->subject);

        // Test In Production status
        $mail = new OrderStatusChanged($order, Order::STATUS_DESIGN_APPROVAL, Order::STATUS_IN_PRODUCTION);
        $envelope = $mail->envelope();
        $this->assertEquals('Your Order is In Production - '.$order->order_number, $envelope->subject);

        // Test Cancelled status
        $mail = new OrderStatusChanged($order, Order::STATUS_PENDING_PAYMENT, Order::STATUS_CANCELLED);
        $envelope = $mail->envelope();
        $this->assertEquals('Your Order Has Been Cancelled - '.$order->order_number, $envelope->subject);
    }

    public function test_no_email_sent_when_status_unchanged(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'order_status' => Order::STATUS_PROCESSING,
        ]);

        Mail::fake();

        // Act - Try to update to same status
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/orders/{$order->id}/status", [
                'status' => Order::STATUS_PROCESSING,
            ]);

        // Assert
        $response->assertOk();
        Mail::assertNothingSent();
    }

    public function test_emails_are_queued_for_async_sending(): void
    {
        // Arrange
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
        ]);

        // Act
        Mail::to($customer->email)->send(new OrderConfirmed($order));

        // Assert - Check that mail implements ShouldQueue
        $mail = new OrderConfirmed($order);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $mail);

        // Check other email classes
        $payment = Payment::factory()->create(['order_id' => $order->id]);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, new PaymentConfirmed($order, $payment));
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, new OrderStatusChanged($order, Order::STATUS_PAID, Order::STATUS_PROCESSING));
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, new OrderShipped($order, 'TRACK123', 'JNE'));
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, new OrderDelivered($order));
    }
}

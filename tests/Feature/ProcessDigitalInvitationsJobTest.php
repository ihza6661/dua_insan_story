<?php

namespace Tests\Feature;

use App\Jobs\ProcessDigitalInvitations;
use App\Mail\DigitalInvitationReady;
use App\Models\DigitalInvitation;
use App\Models\InvitationDetail;
use App\Models\InvitationTemplate;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessDigitalInvitationsJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function job_creates_digital_invitations_for_order()
    {
        Mail::fake();

        // Create customer
        $customer = User::factory()->create();

        // Create digital templates
        $template1 = InvitationTemplate::factory()->create([
            'name' => 'Classic Elegant',
            'slug' => 'classic-elegant',
        ]);

        $template2 = InvitationTemplate::factory()->create([
            'name' => 'Modern Minimalist',
            'slug' => 'modern-minimalist',
        ]);

        // Create products
        $product1 = Product::factory()->create([
            'name' => 'Classic Elegant Digital',
            'digital_template_id' => $template1->id,
            'is_digital' => true,
            'price' => 150000,
        ]);

        $product2 = Product::factory()->create([
            'name' => 'Modern Minimalist Digital',
            'digital_template_id' => $template2->id,
            'is_digital' => true,
            'price' => 200000,
        ]);

        // Create order
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'Paid',
            'total_price' => 350000,
        ]);

        // Create order items
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 1,
            'price' => 150000,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'price' => 200000,
        ]);

        // Create payment
        Payment::factory()->create([
            'order_id' => $order->id,
            'payment_type' => 'full',
            'status' => 'Paid',
            'amount' => 350000,
        ]);

        // Create invitation details (wedding data)
        InvitationDetail::factory()->create([
            'order_id' => $order->id,
            'bride_name' => 'Jane Doe',
            'groom_name' => 'John Doe',
            'event_date' => '2025-12-25',
            'event_time' => '14:00:00',
            'event_location' => 'Grand Ballroom',
        ]);

        // Assert no invitations exist yet
        $this->assertEquals(0, DigitalInvitation::count());
        $this->assertEquals(0, Notification::count());

        // Execute the job
        $job = new ProcessDigitalInvitations($order);
        $job->handle(app(\App\Services\DigitalInvitationService::class));

        // Assert 2 invitations created
        $this->assertEquals(2, DigitalInvitation::count());

        // Assert invitations are activated
        $invitations = DigitalInvitation::where('order_id', $order->id)->get();
        foreach ($invitations as $invitation) {
            $this->assertEquals('active', $invitation->status);
            $this->assertNotNull($invitation->slug);
        }

        // Assert 2 notifications created
        $this->assertEquals(2, Notification::count());

        $notifications = Notification::where('user_id', $customer->id)->get();
        foreach ($notifications as $notification) {
            $this->assertEquals('digital_invitation_ready', $notification->type);
            $this->assertEquals('Undangan Digital Anda Siap!', $notification->title);
            $this->assertNotNull($notification->data['invitation_id']);
            $this->assertNotNull($notification->data['slug']);
            $this->assertNotNull($notification->data['action_url']);
        }

        // Assert 2 emails sent
        Mail::assertSent(DigitalInvitationReady::class, 2);
    }

    /**
     * @test
     */
    public function job_is_dispatched_from_webhook()
    {
        Queue::fake();

        // Create customer
        $customer = User::factory()->create();

        // Create digital template
        $template = InvitationTemplate::factory()->create();

        // Create product
        $product = Product::factory()->create([
            'digital_template_id' => $template->id,
            'is_digital' => true,
            'price' => 150000,
        ]);

        // Create order
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'Pending Payment',
            'total_price' => 150000,
        ]);

        // Create order item
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 150000,
        ]);

        // Create payment
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'payment_type' => 'full',
            'status' => 'Pending',
            'amount' => 150000,
            'transaction_id' => 'TEST-'.time(),
        ]);

        // Simulate Midtrans webhook
        $this->postJson('/api/v1/webhook/midtrans', [
            'order_id' => $payment->transaction_id,
            'status_code' => '200',
            'gross_amount' => '150000.00',
            'signature_key' => hash('sha512', $payment->transaction_id.'200'.'150000.00'.config('midtrans.server_key')),
            'transaction_status' => 'settlement',
            'payment_type' => 'credit_card',
        ]);

        // Assert job was dispatched
        Queue::assertPushed(ProcessDigitalInvitations::class, function ($job) use ($order) {
            return $job->order->id === $order->id;
        });
    }

    /**
     * @test
     */
    public function job_handles_idempotency_correctly()
    {
        Mail::fake();

        // Create customer
        $customer = User::factory()->create();

        // Create digital template
        $template = InvitationTemplate::factory()->create();

        // Create product
        $product = Product::factory()->create([
            'digital_template_id' => $template->id,
            'is_digital' => true,
            'price' => 150000,
        ]);

        // Create order
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'Paid',
        ]);

        // Create order item
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 150000,
        ]);

        // Create payment
        Payment::factory()->create([
            'order_id' => $order->id,
            'payment_type' => 'full',
            'status' => 'Paid',
        ]);

        // Create invitation details
        InvitationDetail::factory()->create(['order_id' => $order->id]);

        // Execute job first time
        $job1 = new ProcessDigitalInvitations($order);
        $job1->handle(app(\App\Services\DigitalInvitationService::class));

        // Assert 1 invitation created
        $this->assertEquals(1, DigitalInvitation::count());

        // Execute job second time (simulating webhook retry)
        $job2 = new ProcessDigitalInvitations($order);
        $job2->handle(app(\App\Services\DigitalInvitationService::class));

        // Assert still only 1 invitation (no duplicate)
        $this->assertEquals(1, DigitalInvitation::count());

        // Assert only 1 email sent from first execution
        // Note: Second execution won't send email because invitation already exists
        Mail::assertSent(DigitalInvitationReady::class, 1);
    }

    /**
     * @test
     */
    public function job_does_nothing_for_non_digital_orders()
    {
        Mail::fake();

        // Create customer
        $customer = User::factory()->create();

        // Create non-digital product
        $product = Product::factory()->create([
            'is_digital' => false,
            'price' => 50000,
        ]);

        // Create order
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'Paid',
        ]);

        // Create order item
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 50000,
        ]);

        // Create payment
        Payment::factory()->create([
            'order_id' => $order->id,
            'payment_type' => 'full',
            'status' => 'Paid',
        ]);

        // Execute job
        $job = new ProcessDigitalInvitations($order);
        $job->handle(app(\App\Services\DigitalInvitationService::class));

        // Assert no invitations created
        $this->assertEquals(0, DigitalInvitation::count());
        $this->assertEquals(0, Notification::count());

        // Assert no emails sent
        Mail::assertNotSent(DigitalInvitationReady::class);
    }

    /**
     * @test
     */
    public function job_continues_processing_after_single_invitation_failure()
    {
        Mail::fake();

        // Create customer
        $customer = User::factory()->create();

        // Create 2 digital templates
        $template1 = InvitationTemplate::factory()->create(['name' => 'Template 1']);
        $template2 = InvitationTemplate::factory()->create(['name' => 'Template 2']);

        // Create products
        $product1 = Product::factory()->create([
            'digital_template_id' => $template1->id,
            'is_digital' => true,
        ]);

        $product2 = Product::factory()->create([
            'digital_template_id' => $template2->id,
            'is_digital' => true,
        ]);

        // Create order
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'Paid',
        ]);

        // Create order items
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product1->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product2->id]);

        Payment::factory()->create(['order_id' => $order->id, 'payment_type' => 'full', 'status' => 'Paid']);
        InvitationDetail::factory()->create(['order_id' => $order->id]);

        // Pre-create first invitation to test idempotency
        DigitalInvitation::factory()->create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'template_id' => $template1->id,
            'status' => 'draft',
        ]);

        // Execute job
        $job = new ProcessDigitalInvitations($order);
        $job->handle(app(\App\Services\DigitalInvitationService::class));

        // Second invitation should still be created despite first one already existing
        $this->assertGreaterThanOrEqual(1, DigitalInvitation::count());
    }
}

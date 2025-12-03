<?php

namespace Tests\Feature;

use App\Mail\CancellationApproved;
use App\Mail\CancellationRejected;
use App\Mail\CancellationRequestAdmin;
use App\Mail\CancellationRequestReceived;
use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected User $admin;

    protected Order $order;

    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        // Create test users
        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->admin = User::factory()->create(['role' => 'admin']);

        // Create product with variant
        $product = Product::factory()->create();
        $this->variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock' => 10,
        ]);

        // Create order with item
        $this->order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'order_status' => Order::STATUS_PAID,
            'total_amount' => 500000,
        ]);

        OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_id' => $product->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 2,
            'unit_price' => 250000,
        ]);
    }

    /** @test */
    public function customer_can_request_cancellation_for_pending_payment_order(): void
    {
        // Arrange
        $this->order->order_status = Order::STATUS_PENDING_PAYMENT;
        $this->order->save();

        // Act
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/orders/{$this->order->id}/cancel", [
                'reason' => 'Changed my mind about the design',
            ]);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Permintaan pembatalan berhasil dibuat. Kami akan segera meninjau permintaan Anda.',
            ]);

        $this->assertDatabaseHas('order_cancellation_requests', [
            'order_id' => $this->order->id,
            'requested_by' => $this->customer->id,
            'cancellation_reason' => 'Changed my mind about the design',
            'status' => 'pending',
        ]);

        // Assert emails queued (not sent directly)
        Mail::assertQueued(CancellationRequestReceived::class, function ($mail) {
            return $mail->hasTo($this->customer->email);
        });

        Mail::assertQueued(CancellationRequestAdmin::class);
    }

    /** @test */
    public function customer_can_request_cancellation_within_24_hours_for_paid_order(): void
    {
        // Arrange - order created just now (within 24 hours)
        $this->order->order_status = Order::STATUS_PAID;
        $this->order->created_at = now()->subHours(12);
        $this->order->save();

        // Act
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/orders/{$this->order->id}/cancel", [
                'reason' => 'Found a better deal elsewhere',
            ]);

        // Assert
        $response->assertStatus(201);

        $this->assertDatabaseHas('order_cancellation_requests', [
            'order_id' => $this->order->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function customer_cannot_request_cancellation_after_24_hours_for_paid_order(): void
    {
        // Arrange - order created 25 hours ago
        $this->order->order_status = Order::STATUS_PAID;
        $this->order->created_at = now()->subHours(25);
        $this->order->save();

        // Act
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/orders/{$this->order->id}/cancel", [
                'reason' => 'Want to cancel',
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonPath('message', 'Pesanan hanya dapat dibatalkan dalam 24 jam setelah pembayaran.');

        $this->assertDatabaseMissing('order_cancellation_requests', [
            'order_id' => $this->order->id,
        ]);
    }

    /** @test */
    public function customer_cannot_cancel_processing_order(): void
    {
        // Arrange
        $this->order->order_status = Order::STATUS_PROCESSING;
        $this->order->save();

        // Act
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/orders/{$this->order->id}/cancel", [
                'reason' => 'Want to cancel',
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonPath('message', 'Pesanan sudah dalam proses produksi dan tidak dapat dibatalkan.');
    }

    /** @test */
    public function customer_cannot_cancel_already_cancelled_order(): void
    {
        // Arrange
        $this->order->order_status = Order::STATUS_CANCELLED;
        $this->order->save();

        // Act
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/orders/{$this->order->id}/cancel", [
                'reason' => 'Want to cancel',
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonPath('message', 'Pesanan sudah dibatalkan atau dikembalikan.');
    }

    /** @test */
    public function customer_cannot_create_duplicate_pending_cancellation_request(): void
    {
        // Arrange - create existing pending request
        OrderCancellationRequest::factory()->create([
            'order_id' => $this->order->id,
            'requested_by' => $this->customer->id,
            'status' => 'pending',
        ]);

        // Act
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/orders/{$this->order->id}/cancel", [
                'reason' => 'Another reason',
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonPath('message', 'Pesanan sudah memiliki permintaan pembatalan yang sedang diproses.');
    }

    /** @test */
    public function cancellation_reason_is_required(): void
    {
        // Act
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/orders/{$this->order->id}/cancel", [
                'reason' => '',
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    /** @test */
    public function admin_can_view_pending_cancellation_requests(): void
    {
        // Arrange
        OrderCancellationRequest::factory()->count(3)->create([
            'status' => 'pending',
        ]);

        OrderCancellationRequest::factory()->create([
            'status' => 'approved',
        ]);

        // Act
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/cancellation-requests?status=pending');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function admin_can_view_single_cancellation_request(): void
    {
        // Arrange
        $request = OrderCancellationRequest::factory()->create([
            'order_id' => $this->order->id,
            'status' => 'pending',
        ]);

        // Act
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/admin/cancellation-requests/{$request->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.id', $request->id)
            ->assertJsonPath('data.order_id', $this->order->id)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_id',
                    'cancellation_reason',
                    'status',
                    'order',
                ],
            ]);
    }

    /** @test */
    public function admin_can_approve_cancellation_request(): void
    {
        // Arrange
        $initialStock = $this->variant->stock;
        $orderQuantity = 2;

        // Create payment so refund is initiated
        \App\Models\Payment::factory()->create([
            'order_id' => $this->order->id,
            'amount' => 500000,
            'status' => 'paid',
        ]);

        $request = OrderCancellationRequest::factory()->create([
            'order_id' => $this->order->id,
            'status' => 'pending',
        ]);

        // Act
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/cancellation-requests/{$request->id}/approve", [
                'notes' => 'Approved as per customer request',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('message', 'Permintaan pembatalan telah disetujui.');

        // Check request updated
        $this->assertDatabaseHas('order_cancellation_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'reviewed_by' => $this->admin->id,
            'admin_notes' => 'Approved as per customer request',
            'stock_restored' => 1,
            'refund_initiated' => 1,
        ]);

        // Check order cancelled
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'order_status' => Order::STATUS_CANCELLED,
        ]);

        // Check stock restored
        $this->variant->refresh();
        $this->assertEquals($initialStock + $orderQuantity, $this->variant->stock);

        // Assert emails queued
        Mail::assertQueued(CancellationApproved::class, function ($mail) {
            return $mail->hasTo($this->order->customer->email);
        });
    }

    /** @test */
    public function admin_can_reject_cancellation_request(): void
    {
        // Arrange
        $request = OrderCancellationRequest::factory()->create([
            'order_id' => $this->order->id,
            'status' => 'pending',
        ]);

        $initialOrderStatus = $this->order->order_status;
        $initialStock = $this->variant->stock;

        // Act
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/cancellation-requests/{$request->id}/reject", [
                'notes' => 'Order is already being processed',
            ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('message', 'Permintaan pembatalan telah ditolak.');

        // Check request updated
        $this->assertDatabaseHas('order_cancellation_requests', [
            'id' => $request->id,
            'status' => 'rejected',
            'reviewed_by' => $this->admin->id,
            'admin_notes' => 'Order is already being processed',
        ]);

        // Check order status unchanged
        $this->order->refresh();
        $this->assertEquals($initialOrderStatus, $this->order->order_status);

        // Check stock unchanged
        $this->variant->refresh();
        $this->assertEquals($initialStock, $this->variant->stock);

        // Assert email queued
        Mail::assertQueued(CancellationRejected::class, function ($mail) {
            return $mail->hasTo($this->order->customer->email);
        });
    }

    /** @test */
    public function admin_notes_required_when_rejecting(): void
    {
        // Arrange
        $request = OrderCancellationRequest::factory()->create([
            'status' => 'pending',
        ]);

        // Act
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/cancellation-requests/{$request->id}/reject", [
                'notes' => '',
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['notes']);
    }

    /** @test */
    public function admin_can_view_cancellation_statistics(): void
    {
        // Arrange
        OrderCancellationRequest::factory()->count(5)->create(['status' => 'pending']);
        OrderCancellationRequest::factory()->count(3)->create(['status' => 'approved']);
        OrderCancellationRequest::factory()->count(2)->create(['status' => 'rejected']);

        // Act
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/cancellation-requests/statistics/summary');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'pending' => 5,
                    'approved' => 3,
                    'rejected' => 2,
                    'total' => 10,
                ],
            ]);
    }

    /** @test */
    public function customer_cannot_access_admin_endpoints(): void
    {
        // Act & Assert
        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/v1/admin/cancellation-requests');

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_request_cancellation(): void
    {
        // Act
        $response = $this->postJson("/api/v1/orders/{$this->order->id}/cancel", [
            'reason' => 'Test',
        ]);

        // Assert
        $response->assertStatus(401);
    }

    /** @test */
    public function customer_can_only_cancel_their_own_orders(): void
    {
        // Arrange
        $otherCustomer = User::factory()->create(['role' => 'customer']);

        // Act
        $response = $this->actingAs($otherCustomer, 'sanctum')
            ->postJson("/api/v1/orders/{$this->order->id}/cancel", [
                'reason' => 'Test',
            ]);

        // Assert
        $response->assertStatus(403);
    }

    /** @test */
    public function stock_restored_correctly_for_multiple_items(): void
    {
        // Arrange
        $variant2 = ProductVariant::factory()->create([
            'product_id' => $this->variant->product_id,
            'stock' => 5,
        ]);

        OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_id' => $this->variant->product_id,
            'product_variant_id' => $variant2->id,
            'quantity' => 3,
        ]);

        $initialStock1 = $this->variant->stock;
        $initialStock2 = $variant2->stock;

        $request = OrderCancellationRequest::factory()->create([
            'order_id' => $this->order->id,
            'status' => 'pending',
        ]);

        // Act
        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/cancellation-requests/{$request->id}/approve");

        // Assert
        $this->variant->refresh();
        $variant2->refresh();

        $this->assertEquals($initialStock1 + 2, $this->variant->stock);
        $this->assertEquals($initialStock2 + 3, $variant2->stock);
    }
}

<?php

namespace Tests\Unit\Services;

use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationService $service;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NotificationService;
        $this->user = User::factory()->create(['role' => 'customer']);
    }

    #[Test]
    public function it_creates_order_status_notification(): void
    {
        $order = Order::factory()->create(['customer_id' => $this->user->id]);

        $notification = $this->service->notifyOrderStatus(
            userId: $this->user->id,
            orderId: $order->id,
            status: 'processing',
            orderNumber: $order->order_number
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($this->user->id, $notification->user_id);
        $this->assertEquals(Notification::TYPE_ORDER_STATUS, $notification->type);
        $this->assertEquals('Pesanan Diproses', $notification->title);
        $this->assertFalse($notification->is_read);
        $this->assertEquals($order->id, $notification->data['order_id']);
        $this->assertEquals($order->order_number, $notification->data['order_number']);
    }

    #[Test]
    public function it_creates_design_proof_notification(): void
    {
        $order = Order::factory()->create(['customer_id' => $this->user->id]);

        $notification = $this->service->notifyDesignProof(
            userId: $this->user->id,
            designProofId: 1,
            action: 'uploaded',
            orderNumber: $order->order_number,
            orderItemId: null
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals(Notification::TYPE_DESIGN_PROOF, $notification->type);
        $this->assertEquals('Desain Baru', $notification->title);
        $this->assertArrayHasKey('design_proof_id', $notification->data);
        $this->assertEquals(1, $notification->data['design_proof_id']);
        $this->assertEquals($order->order_number, $notification->data['order_number']);
    }

    #[Test]
    public function it_creates_payment_notification(): void
    {
        $order = Order::factory()->create(['customer_id' => $this->user->id]);

        $notification = $this->service->notifyPayment(
            userId: $this->user->id,
            orderId: $order->id,
            status: 'success',
            orderNumber: $order->order_number
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals(Notification::TYPE_PAYMENT, $notification->type);
        $this->assertEquals('Pembayaran Berhasil', $notification->title);
        $this->assertEquals('success', $notification->data['payment_status']);
        $this->assertEquals($order->order_number, $notification->data['order_number']);
    }

    #[Test]
    public function it_creates_cancellation_notification(): void
    {
        $order = Order::factory()->create(['customer_id' => $this->user->id]);

        $notification = $this->service->notifyCancellation(
            userId: $this->user->id,
            orderId: $order->id,
            status: 'approved',
            orderNumber: $order->order_number,
            reason: 'Customer request'
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals(Notification::TYPE_CANCELLATION, $notification->type);
        $this->assertEquals('Pembatalan Disetujui', $notification->title);
        $this->assertEquals('approved', $notification->data['cancellation_status']);
        $this->assertEquals('Customer request', $notification->data['reason']);
    }

    #[Test]
    public function it_gets_user_notifications(): void
    {
        // Create notifications
        Notification::factory()->count(3)->create(['user_id' => $this->user->id]);
        Notification::factory()->count(2)->create(); // Other user's notifications

        $notifications = $this->service->getUserNotifications($this->user->id);

        $this->assertCount(3, $notifications);
        $this->assertEquals($this->user->id, $notifications->first()->user_id);
    }

    #[Test]
    public function it_gets_unread_count(): void
    {
        Notification::factory()->count(3)->unread()->create(['user_id' => $this->user->id]);
        Notification::factory()->count(2)->read()->create(['user_id' => $this->user->id]);

        $count = $this->service->getUnreadCount($this->user->id);

        $this->assertEquals(3, $count);
    }

    #[Test]
    public function it_marks_notification_as_read(): void
    {
        $notification = Notification::factory()->unread()->create([
            'user_id' => $this->user->id,
        ]);

        $result = $this->service->markAsRead($notification->id, $this->user->id);

        $this->assertTrue($result);
        $notification->refresh();
        $this->assertTrue($notification->is_read);
        $this->assertNotNull($notification->read_at);
    }

    #[Test]
    public function it_returns_false_when_marking_nonexistent_notification_as_read(): void
    {
        $result = $this->service->markAsRead(99999, $this->user->id);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_returns_false_when_marking_another_users_notification_as_read(): void
    {
        $otherUser = User::factory()->create();
        $notification = Notification::factory()->unread()->create([
            'user_id' => $otherUser->id,
        ]);

        $result = $this->service->markAsRead($notification->id, $this->user->id);

        $this->assertFalse($result);
        $notification->refresh();
        $this->assertFalse($notification->is_read);
    }

    #[Test]
    public function it_marks_all_notifications_as_read(): void
    {
        Notification::factory()->count(3)->unread()->create([
            'user_id' => $this->user->id,
        ]);

        $count = $this->service->markAllAsRead($this->user->id);

        $this->assertEquals(3, $count);

        $unreadCount = Notification::where('user_id', $this->user->id)
            ->unread()
            ->count();

        $this->assertEquals(0, $unreadCount);
    }

    #[Test]
    public function it_paginates_notifications(): void
    {
        Notification::factory()->count(15)->create(['user_id' => $this->user->id]);

        $notifications = $this->service->getUserNotifications($this->user->id, 10);

        $this->assertCount(10, $notifications);
        $this->assertEquals(15, $notifications->total());
    }

    #[Test]
    public function it_deletes_old_read_notifications(): void
    {
        // Create old read notifications (older than 90 days)
        Notification::factory()->count(3)->read()->create([
            'user_id' => $this->user->id,
            'read_at' => now()->subDays(100),
        ]);

        // Create recent read notifications
        Notification::factory()->count(2)->read()->create([
            'user_id' => $this->user->id,
            'read_at' => now()->subDays(30),
        ]);

        // Create unread notifications
        Notification::factory()->count(2)->unread()->create([
            'user_id' => $this->user->id,
        ]);

        $deletedCount = $this->service->deleteOldNotifications(90);

        $this->assertEquals(3, $deletedCount);
        $this->assertEquals(4, Notification::where('user_id', $this->user->id)->count());
    }

    #[Test]
    public function it_creates_all_order_status_types(): void
    {
        $order = Order::factory()->create(['customer_id' => $this->user->id]);
        $statuses = ['pending_payment', 'paid', 'processing', 'shipped', 'delivered', 'cancelled'];

        foreach ($statuses as $status) {
            $notification = $this->service->notifyOrderStatus(
                userId: $this->user->id,
                orderId: $order->id,
                status: $status,
                orderNumber: $order->order_number
            );

            $this->assertEquals(Notification::TYPE_ORDER_STATUS, $notification->type);
            $this->assertEquals($status, $notification->data['status']);
        }

        $this->assertEquals(count($statuses), Notification::where('user_id', $this->user->id)->count());
    }

    #[Test]
    public function it_creates_all_design_proof_action_types(): void
    {
        $order = Order::factory()->create(['customer_id' => $this->user->id]);
        $actions = ['uploaded', 'approved', 'revision_requested', 'rejected'];

        foreach ($actions as $action) {
            $notification = $this->service->notifyDesignProof(
                userId: $this->user->id,
                designProofId: 1,
                action: $action,
                orderNumber: $order->order_number
            );

            $this->assertEquals(Notification::TYPE_DESIGN_PROOF, $notification->type);
            $this->assertEquals($action, $notification->data['action']);
        }

        $this->assertEquals(count($actions), Notification::where('user_id', $this->user->id)->count());
    }
}

<?php

namespace Tests\Unit;

use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityLoggerTest extends TestCase
{
    use RefreshDatabase;

    protected ActivityLogger $activityLogger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->activityLogger = new ActivityLogger();
    }

    #[Test]
    public function it_can_log_basic_activity()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create();

        $log = $this->activityLogger->log(
            logType: 'test_action',
            action: 'created',
            subject: $order,
            user: $user,
            description: 'Test description',
            properties: ['test_key' => 'test_value']
        );

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertEquals('test_action', $log->log_type);
        $this->assertEquals('created', $log->action);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertEquals($user->full_name, $log->user_name);
        $this->assertEquals(Order::class, $log->subject_type);
        $this->assertEquals($order->id, $log->subject_id);
        $this->assertEquals('Test description', $log->description);
        $this->assertEquals(['test_key' => 'test_value'], $log->properties);
    }

    #[Test]
    public function it_can_log_cancellation_request_creation()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $cancellationRequest = OrderCancellationRequest::factory()->create([
            'order_id' => $order->id,
            'requested_by' => $customer->id,
        ]);

        $log = $this->activityLogger->logCancellationRequestCreated($cancellationRequest, $customer);

        $this->assertEquals('order_cancellation', $log->log_type);
        $this->assertEquals('created', $log->action);
        $this->assertEquals($customer->id, $log->user_id);
        $this->assertStringContainsString($customer->full_name, $log->description);
        $this->assertStringContainsString($order->order_number, $log->description);
        $this->assertArrayHasKey('order_id', $log->properties);
        $this->assertArrayHasKey('order_number', $log->properties);
    }

    #[Test]
    public function it_can_log_cancellation_approval()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $cancellationRequest = OrderCancellationRequest::factory()->create([
            'order_id' => $order->id,
            'requested_by' => $customer->id,
            'status' => OrderCancellationRequest::STATUS_APPROVED,
            'reviewed_by' => $admin->id,
        ]);

        $log = $this->activityLogger->logCancellationApproved(
            $cancellationRequest,
            $admin,
            'Approved due to valid reason',
            500000
        );

        $this->assertEquals('order_cancellation', $log->log_type);
        $this->assertEquals('approved', $log->action);
        $this->assertEquals($admin->id, $log->user_id);
        $this->assertEquals($admin->full_name, $log->user_name);
        $this->assertStringContainsString('approved', $log->description);
        $this->assertEquals('Approved due to valid reason', $log->properties['admin_notes']);
        $this->assertEquals(500000, $log->properties['refund_amount']);
        $this->assertEquals('pending', $log->properties['old_status']);
        $this->assertEquals('approved', $log->properties['new_status']);
    }

    #[Test]
    public function it_can_log_cancellation_rejection()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $cancellationRequest = OrderCancellationRequest::factory()->create([
            'order_id' => $order->id,
            'requested_by' => $customer->id,
            'status' => OrderCancellationRequest::STATUS_REJECTED,
            'reviewed_by' => $admin->id,
        ]);

        $log = $this->activityLogger->logCancellationRejected(
            $cancellationRequest,
            $admin,
            'Order already in production'
        );

        $this->assertEquals('order_cancellation', $log->log_type);
        $this->assertEquals('rejected', $log->action);
        $this->assertEquals($admin->id, $log->user_id);
        $this->assertStringContainsString('rejected', $log->description);
        $this->assertEquals('Order already in production', $log->properties['admin_notes']);
    }

    #[Test]
    public function it_can_retrieve_activity_for_subject()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();

        // Create multiple logs for the same subject
        $this->activityLogger->log('test', 'created', $order, $user);
        $this->activityLogger->log('test', 'updated', $order, $user);
        $this->activityLogger->log('test', 'deleted', $order, $user);

        $logs = $this->activityLogger->getActivityForSubject($order);

        $this->assertCount(3, $logs);
        // Verify all actions are present
        $actions = $logs->pluck('action')->toArray();
        $this->assertContains('created', $actions);
        $this->assertContains('updated', $actions);
        $this->assertContains('deleted', $actions);
    }

    #[Test]
    public function it_can_retrieve_activity_by_type()
    {
        $user = User::factory()->create();
        $order1 = Order::factory()->create();
        $order2 = Order::factory()->create();

        $this->activityLogger->log('order_cancellation', 'created', $order1, $user);
        $this->activityLogger->log('order_cancellation', 'approved', $order2, $user);
        $this->activityLogger->log('order_status_change', 'updated', $order1, $user);

        $logs = $this->activityLogger->getRecentByType('order_cancellation');

        $this->assertCount(2, $logs);
        foreach ($logs as $log) {
            $this->assertEquals('order_cancellation', $log->log_type);
        }
    }

    #[Test]
    public function it_can_retrieve_activity_by_user()
    {
        $admin1 = User::factory()->create(['role' => 'admin']);
        $admin2 = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create();

        $this->activityLogger->log('test', 'action1', $order, $admin1);
        $this->activityLogger->log('test', 'action2', $order, $admin1);
        $this->activityLogger->log('test', 'action3', $order, $admin2);

        $logs = $this->activityLogger->getActivityByUser($admin1);

        $this->assertCount(2, $logs);
        foreach ($logs as $log) {
            $this->assertEquals($admin1->id, $log->user_id);
        }
    }

    #[Test]
    public function it_stores_ip_address_and_user_agent()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();

        // Simulate a request with IP and user agent
        $this->app['request']->server->set('REMOTE_ADDR', '127.0.0.1');
        $this->app['request']->headers->set('User-Agent', 'TestBrowser/1.0');

        $log = $this->activityLogger->log('test', 'created', $order, $user);

        $this->assertEquals('127.0.0.1', $log->ip_address);
        $this->assertEquals('TestBrowser/1.0', $log->user_agent);
    }

    #[Test]
    public function activity_log_has_relationships()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();

        $log = $this->activityLogger->log('test', 'created', $order, $user);

        // Test user relationship
        $this->assertInstanceOf(User::class, $log->user);
        $this->assertEquals($user->id, $log->user->id);

        // Test subject relationship
        $this->assertInstanceOf(Order::class, $log->subject);
        $this->assertEquals($order->id, $log->subject->id);
    }

    #[Test]
    public function cancellation_request_can_access_activity_logs()
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $cancellationRequest = OrderCancellationRequest::factory()->create([
            'order_id' => $order->id,
            'requested_by' => $customer->id,
        ]);

        // Create some activity logs
        $this->activityLogger->logCancellationRequestCreated($cancellationRequest, $customer);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->activityLogger->logCancellationApproved($cancellationRequest, $admin, 'Approved', 100000);

        // Access logs through relationship
        $logs = $cancellationRequest->activityLogs;

        $this->assertCount(2, $logs);
        $this->assertEquals('order_cancellation', $logs->first()->log_type);
    }
}

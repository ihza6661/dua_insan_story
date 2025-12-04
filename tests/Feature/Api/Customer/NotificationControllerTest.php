<?php

namespace Tests\Feature\Api\Customer;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->create(['role' => 'customer']);
    }

    #[Test]
    public function it_gets_user_notifications(): void
    {
        // Create notifications for this user
        Notification::factory()->count(5)->create(['user_id' => $this->customer->id]);
        
        // Create notifications for another user (should not be returned)
        Notification::factory()->count(3)->create();

        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'user_id',
                            'type',
                            'title',
                            'message',
                            'data',
                            'is_read',
                            'read_at',
                            'created_at',
                        ],
                    ],
                    'current_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonCount(5, 'data.data');
    }

    #[Test]
    public function it_paginates_notifications(): void
    {
        Notification::factory()->count(25)->create(['user_id' => $this->customer->id]);

        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/notifications?per_page=10');

        $response->assertOk()
            ->assertJsonCount(10, 'data.data')
            ->assertJsonPath('data.total', 25)
            ->assertJsonPath('data.per_page', 10);
    }

    #[Test]
    public function it_gets_unread_count(): void
    {
        Notification::factory()->count(5)->unread()->create(['user_id' => $this->customer->id]);
        Notification::factory()->count(3)->read()->create(['user_id' => $this->customer->id]);

        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/notifications/unread-count');

        $response->assertOk()
            ->assertJson([
                'message' => 'Unread count retrieved successfully',
                'data' => [
                    'unread_count' => 5,
                ],
            ]);
    }

    #[Test]
    public function it_marks_notification_as_read(): void
    {
        $notification = Notification::factory()->unread()->create([
            'user_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson("/api/v1/notifications/{$notification->id}/mark-as-read");

        $response->assertOk()
            ->assertJson([
                'message' => 'Notification marked as read',
            ]);

        $notification->refresh();
        $this->assertTrue($notification->is_read);
        $this->assertNotNull($notification->read_at);
    }

    #[Test]
    public function it_returns_404_when_marking_nonexistent_notification(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/notifications/99999/mark-as-read');

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Notification not found',
            ]);
    }

    #[Test]
    public function it_cannot_mark_another_users_notification_as_read(): void
    {
        $otherUser = User::factory()->create(['role' => 'customer']);
        $notification = Notification::factory()->unread()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson("/api/v1/notifications/{$notification->id}/mark-as-read");

        $response->assertNotFound();

        $notification->refresh();
        $this->assertFalse($notification->is_read);
    }

    #[Test]
    public function it_marks_all_notifications_as_read(): void
    {
        Notification::factory()->count(5)->unread()->create([
            'user_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/notifications/mark-all-read');

        $response->assertOk()
            ->assertJson([
                'message' => 'All notifications marked as read',
                'data' => [
                    'updated_count' => 5,
                ],
            ]);

        $unreadCount = Notification::where('user_id', $this->customer->id)
            ->unread()
            ->count();

        $this->assertEquals(0, $unreadCount);
    }

    #[Test]
    public function it_only_marks_own_notifications_as_read(): void
    {
        // Create notifications for this user
        Notification::factory()->count(3)->unread()->create([
            'user_id' => $this->customer->id,
        ]);

        // Create notifications for another user
        $otherUser = User::factory()->create(['role' => 'customer']);
        Notification::factory()->count(2)->unread()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/notifications/mark-all-read');

        $response->assertOk()
            ->assertJsonPath('data.updated_count', 3);

        // Verify other user's notifications are still unread
        $otherUnreadCount = Notification::where('user_id', $otherUser->id)
            ->unread()
            ->count();

        $this->assertEquals(2, $otherUnreadCount);
    }

    #[Test]
    public function it_requires_authentication_to_get_notifications(): void
    {
        $response = $this->getJson('/api/v1/notifications');

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_requires_authentication_to_get_unread_count(): void
    {
        $response = $this->getJson('/api/v1/notifications/unread-count');

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_requires_authentication_to_mark_as_read(): void
    {
        $notification = Notification::factory()->create();

        $response = $this->postJson("/api/v1/notifications/{$notification->id}/mark-as-read");

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_requires_authentication_to_mark_all_as_read(): void
    {
        $response = $this->postJson('/api/v1/notifications/mark-all-read');

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_returns_notifications_in_descending_order(): void
    {
        // Create notifications with different timestamps
        $old = Notification::factory()->create([
            'user_id' => $this->customer->id,
            'created_at' => now()->subDays(5),
        ]);

        $middle = Notification::factory()->create([
            'user_id' => $this->customer->id,
            'created_at' => now()->subDays(2),
        ]);

        $recent = Notification::factory()->create([
            'user_id' => $this->customer->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/notifications');

        $response->assertOk();

        $notifications = $response->json('data.data');
        $this->assertEquals($recent->id, $notifications[0]['id']);
        $this->assertEquals($middle->id, $notifications[1]['id']);
        $this->assertEquals($old->id, $notifications[2]['id']);
    }

    #[Test]
    public function it_handles_empty_notification_list(): void
    {
        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonCount(0, 'data.data')
            ->assertJsonPath('data.total', 0);
    }

    #[Test]
    public function it_handles_zero_unread_count(): void
    {
        Notification::factory()->count(3)->read()->create([
            'user_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/notifications/unread-count');

        $response->assertOk()
            ->assertJsonPath('data.unread_count', 0);
    }

    #[Test]
    public function it_returns_correct_notification_types(): void
    {
        Notification::factory()->create([
            'user_id' => $this->customer->id,
            'type' => Notification::TYPE_ORDER_STATUS,
        ]);

        Notification::factory()->create([
            'user_id' => $this->customer->id,
            'type' => Notification::TYPE_DESIGN_PROOF,
        ]);

        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/notifications');

        $response->assertOk();

        $notifications = collect($response->json('data.data'));
        $this->assertTrue($notifications->contains('type', Notification::TYPE_ORDER_STATUS));
        $this->assertTrue($notifications->contains('type', Notification::TYPE_DESIGN_PROOF));
    }
}

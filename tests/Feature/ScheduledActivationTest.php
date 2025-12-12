<?php

namespace Tests\Feature;

use App\Console\Commands\ActivateScheduledInvitations;
use App\Models\DigitalInvitation;
use App\Models\InvitationTemplate;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ScheduledActivationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected InvitationTemplate $template;
    protected DigitalInvitation $draftInvitation;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create([
            'email' => 'customer@test.com',
            'role' => 'customer',
        ]);

        // Create category
        $category = ProductCategory::factory()->create([
            'name' => 'Digital Invitations',
        ]);

        // Create template
        $this->template = InvitationTemplate::factory()->create([
            'name' => 'Sakeenah Islamic',
            'slug' => 'sakeenah-islamic',
            'is_active' => true,
        ]);

        // Create digital product
        Product::factory()->create([
            'name' => 'Digital Invitation - Sakeenah',
            'category_id' => $category->id,
            'product_type' => 'digital',
            'template_id' => $this->template->id,
        ]);

        // Create order
        $order = Order::factory()->create([
            'customer_id' => $this->user->id,
            'order_status' => 'Paid',
            'total_amount' => 150000,
        ]);

        // Create DRAFT invitation
        $this->draftInvitation = DigitalInvitation::factory()->create([
            'user_id' => $this->user->id,
            'order_id' => $order->id,
            'template_id' => $this->template->id,
            'slug' => 'test-draft-invitation',
            'status' => DigitalInvitation::STATUS_DRAFT,
            'expires_at' => now()->addYear(),
        ]);

        // Create invitation data
        $this->draftInvitation->data()->create([
            'digital_invitation_id' => $this->draftInvitation->id,
            'bride_name' => 'Sarah',
            'groom_name' => 'Michael',
            'event_date' => '2025-08-15',
            'venue_name' => 'Grand Ballroom',
        ]);
    }

    /** @test */
    public function authenticated_user_can_schedule_activation()
    {
        $scheduledTime = now()->addDays(3)->format('Y-m-d H:i:s');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/digital-invitations/{$this->draftInvitation->id}/schedule-activation", [
                'scheduled_at' => $scheduledTime,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Invitation scheduled for activation successfully',
            ]);

        // Verify database
        $this->draftInvitation->refresh();
        $this->assertNotNull($this->draftInvitation->scheduled_activation_at);
        $this->assertEquals(
            $scheduledTime,
            $this->draftInvitation->scheduled_activation_at->format('Y-m-d H:i:s')
        );
        $this->assertEquals(DigitalInvitation::STATUS_DRAFT, $this->draftInvitation->status);
    }

    /** @test */
    public function user_can_update_existing_schedule()
    {
        // Set initial schedule
        $this->draftInvitation->update([
            'scheduled_activation_at' => now()->addDays(3),
        ]);

        // Update to new time
        $newScheduledTime = now()->addDays(7)->format('Y-m-d H:i:s');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/digital-invitations/{$this->draftInvitation->id}/schedule-activation", [
                'scheduled_at' => $newScheduledTime,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Scheduled activation updated successfully',
            ]);

        // Verify update
        $this->draftInvitation->refresh();
        $this->assertEquals(
            $newScheduledTime,
            $this->draftInvitation->scheduled_activation_at->format('Y-m-d H:i:s')
        );
    }

    /** @test */
    public function user_can_cancel_scheduled_activation()
    {
        // Set schedule
        $this->draftInvitation->update([
            'scheduled_activation_at' => now()->addDays(3),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/digital-invitations/{$this->draftInvitation->id}/schedule-activation");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Scheduled activation cancelled successfully',
            ]);

        // Verify cancellation
        $this->draftInvitation->refresh();
        $this->assertNull($this->draftInvitation->scheduled_activation_at);
    }

    /** @test */
    public function cannot_schedule_activation_in_the_past()
    {
        $pastTime = now()->subHours(2)->format('Y-m-d H:i:s');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/digital-invitations/{$this->draftInvitation->id}/schedule-activation", [
                'scheduled_at' => $pastTime,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_at']);
    }

    /** @test */
    public function cannot_schedule_activation_for_active_invitation()
    {
        // Activate invitation
        $this->draftInvitation->update([
            'status' => DigitalInvitation::STATUS_ACTIVE,
            'activated_at' => now(),
        ]);

        $scheduledTime = now()->addDays(3)->format('Y-m-d H:i:s');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/digital-invitations/{$this->draftInvitation->id}/schedule-activation", [
                'scheduled_at' => $scheduledTime,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Only draft invitations can be scheduled for activation.',
            ]);
    }

    /** @test */
    public function guest_cannot_schedule_activation()
    {
        $scheduledTime = now()->addDays(3)->format('Y-m-d H:i:s');

        $response = $this->postJson("/api/v1/digital-invitations/{$this->draftInvitation->id}/schedule-activation", [
            'scheduled_at' => $scheduledTime,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function user_cannot_schedule_another_users_invitation()
    {
        /** @var User $otherUser */
        $otherUser = User::factory()->create([
            'email' => 'other@test.com',
            'role' => 'customer',
        ]);

        $scheduledTime = now()->addDays(3)->format('Y-m-d H:i:s');

        $response = $this->actingAs($otherUser, 'sanctum')
            ->postJson("/api/v1/digital-invitations/{$this->draftInvitation->id}/schedule-activation", [
                'scheduled_at' => $scheduledTime,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function scheduled_at_field_is_required()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/digital-invitations/{$this->draftInvitation->id}/schedule-activation", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_at']);
    }

    /** @test */
    public function scheduled_at_must_be_valid_date_format()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/digital-invitations/{$this->draftInvitation->id}/schedule-activation", [
                'scheduled_at' => 'invalid-date',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_at']);
    }

    /** @test */
    public function artisan_command_activates_scheduled_invitations()
    {
        Mail::fake();

        // Create multiple scheduled invitations
        $invitation1 = DigitalInvitation::factory()->create([
            'user_id' => $this->user->id,
            'order_id' => $this->draftInvitation->order_id,
            'template_id' => $this->template->id,
            'slug' => 'scheduled-1',
            'status' => DigitalInvitation::STATUS_DRAFT,
            'scheduled_activation_at' => now()->subMinutes(5), // Past due
            'expires_at' => now()->addYear(),
        ]);
        $invitation1->data()->create([
            'digital_invitation_id' => $invitation1->id,
            'bride_name' => 'Test1',
            'groom_name' => 'Test2',
        ]);

        $invitation2 = DigitalInvitation::factory()->create([
            'user_id' => $this->user->id,
            'order_id' => $this->draftInvitation->order_id,
            'template_id' => $this->template->id,
            'slug' => 'scheduled-2',
            'status' => DigitalInvitation::STATUS_DRAFT,
            'scheduled_activation_at' => now()->addDays(3), // Future
            'expires_at' => now()->addYear(),
        ]);
        $invitation2->data()->create([
            'digital_invitation_id' => $invitation2->id,
            'bride_name' => 'Test3',
            'groom_name' => 'Test4',
        ]);

        // Run command
        Artisan::call(ActivateScheduledInvitations::class);

        // Verify invitation1 is activated
        $invitation1->refresh();
        $this->assertEquals(DigitalInvitation::STATUS_ACTIVE, $invitation1->status);
        $this->assertNotNull($invitation1->activated_at);
        $this->assertNull($invitation1->scheduled_activation_at);

        // Verify invitation2 is still draft
        $invitation2->refresh();
        $this->assertEquals(DigitalInvitation::STATUS_DRAFT, $invitation2->status);
        $this->assertNull($invitation2->activated_at);
        $this->assertNotNull($invitation2->scheduled_activation_at);
    }

    /** @test */
    public function artisan_command_sends_email_after_activation()
    {
        Mail::fake();

        // Create scheduled invitation
        $invitation = DigitalInvitation::factory()->create([
            'user_id' => $this->user->id,
            'order_id' => $this->draftInvitation->order_id,
            'template_id' => $this->template->id,
            'slug' => 'scheduled-email-test',
            'status' => DigitalInvitation::STATUS_DRAFT,
            'scheduled_activation_at' => now()->subMinutes(5),
            'expires_at' => now()->addYear(),
        ]);
        $invitation->data()->create([
            'digital_invitation_id' => $invitation->id,
            'bride_name' => 'Email',
            'groom_name' => 'Test',
        ]);

        // Run command
        Artisan::call(ActivateScheduledInvitations::class);

        // Verify email sent
        Mail::assertSent(\App\Mail\DigitalInvitationReady::class, function ($mail) {
            return $mail->hasTo($this->user->email);
        });
    }

    /** @test */
    public function artisan_command_outputs_activation_count()
    {
        // Create 3 scheduled invitations
        for ($i = 1; $i <= 3; $i++) {
            $inv = DigitalInvitation::factory()->create([
                'user_id' => $this->user->id,
                'order_id' => $this->draftInvitation->order_id,
                'template_id' => $this->template->id,
                'slug' => "scheduled-count-{$i}",
                'status' => DigitalInvitation::STATUS_DRAFT,
                'scheduled_activation_at' => now()->subMinutes(5),
                'expires_at' => now()->addYear(),
            ]);
            $inv->data()->create([
                'digital_invitation_id' => $inv->id,
                'bride_name' => "Bride{$i}",
                'groom_name' => "Groom{$i}",
            ]);
        }

        // Run command and capture output
        $this->artisan(ActivateScheduledInvitations::class)
            ->expectsOutput('Activated 3 invitation(s).')
            ->assertExitCode(0);
    }

    /** @test */
    public function artisan_command_handles_zero_scheduled_invitations()
    {
        $this->artisan(ActivateScheduledInvitations::class)
            ->expectsOutput('No invitations to activate.')
            ->assertExitCode(0);
    }

    /** @test */
    public function cancel_schedule_returns_404_if_no_schedule_exists()
    {
        // Ensure no schedule
        $this->draftInvitation->update(['scheduled_activation_at' => null]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/digital-invitations/{$this->draftInvitation->id}/schedule-activation");

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'No scheduled activation found for this invitation.',
            ]);
    }

    /** @test */
    public function scheduled_activation_persists_across_edits()
    {
        // Set schedule
        $scheduledTime = now()->addDays(5);
        $this->draftInvitation->update([
            'scheduled_activation_at' => $scheduledTime,
        ]);

        // Edit invitation data
        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/digital-invitations/{$this->draftInvitation->id}/customize", [
                'bride_name' => 'Updated Sarah',
                'groom_name' => 'Updated Michael',
                'event_date' => '2025-09-01',
                'venue_name' => 'New Venue',
            ]);

        // Verify schedule still exists
        $this->draftInvitation->refresh();
        $this->assertNotNull($this->draftInvitation->scheduled_activation_at);
        $this->assertEquals(
            $scheduledTime->format('Y-m-d H:i:s'),
            $this->draftInvitation->scheduled_activation_at->format('Y-m-d H:i:s')
        );
    }
}

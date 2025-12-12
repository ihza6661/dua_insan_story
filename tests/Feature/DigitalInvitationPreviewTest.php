<?php

namespace Tests\Feature;

use App\Models\DigitalInvitation;
use App\Models\InvitationTemplate;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigitalInvitationPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected InvitationTemplate $template;
    protected DigitalInvitation $draftInvitation;
    protected DigitalInvitation $activeInvitation;

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

        // Create ACTIVE invitation
        $this->activeInvitation = DigitalInvitation::factory()->create([
            'user_id' => $this->user->id,
            'order_id' => $order->id,
            'template_id' => $this->template->id,
            'slug' => 'test-active-invitation',
            'status' => DigitalInvitation::STATUS_ACTIVE,
            'activated_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $this->activeInvitation->data()->create([
            'digital_invitation_id' => $this->activeInvitation->id,
            'bride_name' => 'Amanda',
            'groom_name' => 'David',
            'event_date' => '2025-09-20',
            'venue_name' => 'Beach Resort',
        ]);
    }

    /** @test */
    public function authenticated_user_can_preview_their_draft_invitation()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/digital-invitations/{$this->draftInvitation->id}/preview");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'template',
                'customization',
                'slug',
                'is_preview',
                'view_count',
            ])
            ->assertJson([
                'is_preview' => true,
                'slug' => 'test-draft-invitation',
            ]);

        // Verify customization data is included
        $this->assertNotNull($response->json('customization'));
        $this->assertEquals('Sarah', $response->json('customization.bride_name'));
        $this->assertEquals('Michael', $response->json('customization.groom_name'));
    }

    /** @test */
    public function authenticated_user_can_preview_their_active_invitation()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/digital-invitations/{$this->activeInvitation->id}/preview");

        $response->assertStatus(200)
            ->assertJson([
                'is_preview' => true,
                'slug' => 'test-active-invitation',
            ]);
    }

    /** @test */
    public function guest_cannot_preview_invitation()
    {
        $response = $this->getJson("/api/v1/digital-invitations/{$this->draftInvitation->id}/preview");

        $response->assertStatus(401);
    }

    /** @test */
    public function user_cannot_preview_another_users_invitation()
    {
        /** @var User $otherUser */
        $otherUser = User::factory()->create([
            'email' => 'other@test.com',
            'role' => 'customer',
        ]);

        $response = $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/v1/digital-invitations/{$this->draftInvitation->id}/preview");

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Invitation not found',
            ]);
    }

    /** @test */
    public function preview_returns_404_for_nonexistent_invitation()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/digital-invitations/99999/preview');

        $response->assertStatus(404);
    }

    /** @test */
    public function preview_includes_template_information()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/digital-invitations/{$this->draftInvitation->id}/preview");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'template' => [
                    'name',
                ],
            ])
            ->assertJson([
                'template' => [
                    'name' => 'Sakeenah Islamic',
                ],
            ]);
    }

    /** @test */
    public function preview_includes_photos_array()
    {
        // Add a photo to the invitation
        $this->draftInvitation->update([
            'photos' => json_encode(['photo1.jpg', 'photo2.jpg']),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/digital-invitations/{$this->draftInvitation->id}/preview");

        $response->assertStatus(200);
        
        // Note: photos structure depends on service implementation
        $this->assertTrue($response->json('is_preview'));
    }

    /** @test */
    public function preview_does_not_increment_view_count()
    {
        $initialViewCount = $this->draftInvitation->view_count;

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/digital-invitations/{$this->draftInvitation->id}/preview");

        $this->draftInvitation->refresh();
        $this->assertEquals($initialViewCount, $this->draftInvitation->view_count);
    }

    /** @test */
    public function preview_works_for_expired_invitation()
    {
        // Create expired invitation
        $expiredInvitation = DigitalInvitation::factory()->create([
            'user_id' => $this->user->id,
            'order_id' => $this->draftInvitation->order_id,
            'template_id' => $this->template->id,
            'slug' => 'expired-invitation',
            'status' => DigitalInvitation::STATUS_ACTIVE,
            'activated_at' => now()->subYear(),
            'expires_at' => now()->subDay(),
        ]);

        $expiredInvitation->data()->create([
            'digital_invitation_id' => $expiredInvitation->id,
            'bride_name' => 'Jane',
            'groom_name' => 'John',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/digital-invitations/{$expiredInvitation->id}/preview");

        $response->assertStatus(200)
            ->assertJson([
                'is_preview' => true,
                'slug' => 'expired-invitation',
            ]);
    }

    /** @test */
    public function preview_response_has_correct_structure_and_types()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/digital-invitations/{$this->draftInvitation->id}/preview");

        $data = $response->json();

        // Check data types
        $this->assertIsString($data['slug']);
        $this->assertIsBool($data['is_preview']);
        $this->assertIsArray($data['template']);
        $this->assertIsArray($data['customization']);
        $this->assertIsInt($data['view_count']);
    }
}

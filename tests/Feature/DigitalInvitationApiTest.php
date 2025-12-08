<?php

namespace Tests\Feature;

use App\Models\DigitalInvitation;
use App\Models\InvitationTemplate;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DigitalInvitationApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected InvitationTemplate $template;

    protected Product $product;

    protected Order $order;

    protected DigitalInvitation $invitation;

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
        $this->product = Product::factory()->create([
            'name' => 'Digital Invitation - Sakeenah',
            'category_id' => $category->id,
            'product_type' => 'digital',
            'template_id' => $this->template->id,
        ]);

        // Create order
        $this->order = Order::factory()->create([
            'customer_id' => $this->user->id,
            'order_status' => 'Paid',
            'total_amount' => 150000,
        ]);

        // Create invitation
        $this->invitation = DigitalInvitation::factory()->create([
            'user_id' => $this->user->id,
            'order_id' => $this->order->id,
            'template_id' => $this->template->id,
            'slug' => 'test-invitation',
            'status' => DigitalInvitation::STATUS_DRAFT,
        ]);

        // Create invitation data
        $this->invitation->data()->create([
            'digital_invitation_id' => $this->invitation->id,
            'bride_name' => 'Sari',
            'groom_name' => 'Andi',
            'event_date' => '2025-08-15',
            'venue_name' => 'Grand Ballroom',
        ]);
    }

    /** @test */
    public function it_can_list_invitation_templates()
    {
        $response = $this->getJson('/api/v1/customer/invitation-templates');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'slug',
                        'name',
                        'description',
                        'thumbnail_image',
                        'price',
                        'template_component',
                        'usage_count',
                    ],
                ],
            ])
            ->assertJsonFragment([
                'slug' => 'sakeenah-islamic',
            ]);
    }

    /** @test */
    public function it_can_show_invitation_template_by_slug()
    {
        $response = $this->getJson('/api/v1/customer/invitation-templates/sakeenah-islamic');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'slug',
                    'name',
                    'description',
                    'thumbnail_image',
                    'price',
                    'template_component',
                    'usage_count',
                    'created_at',
                ],
            ])
            ->assertJsonFragment([
                'slug' => 'sakeenah-islamic',
            ]);
    }

    /** @test */
    public function it_can_list_user_digital_invitations()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/digital-invitations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'order_id',
                        'slug',
                        'status',
                        'public_url',
                        'view_count',
                        'expires_at',
                        'is_expired',
                        'template',
                        'order',
                        'customization_data',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJsonFragment([
                'slug' => 'test-invitation',
            ]);
    }

    /** @test */
    public function it_can_show_user_digital_invitation()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/digital-invitations/{$this->invitation->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'slug',
                    'status',
                    'template',
                    'customization_data' => [
                        'bride_name',
                        'groom_name',
                        'event_date',
                        'venue_name',
                    ],
                ],
            ])
            ->assertJsonFragment([
                'bride_name' => 'Sari',
                'groom_name' => 'Andi',
            ]);
    }

    /** @test */
    public function it_can_update_invitation_customization()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/digital-invitations/{$this->invitation->id}/customize", [
                'bride_name' => 'Siti',
                'groom_name' => 'Ahmad',
                'event_date' => '2025-09-20',
                'event_time' => '14:00',
                'venue_name' => 'Masjid Raya',
                'venue_address' => 'Jl. Raya No. 123',
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Invitation customization updated successfully',
            ]);

        $this->assertDatabaseHas('digital_invitation_data', [
            'digital_invitation_id' => $this->invitation->id,
            'bride_name' => 'Siti',
            'groom_name' => 'Ahmad',
        ]);
    }

    /** @test */
    public function it_can_upload_photo()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/digital-invitations/{$this->invitation->id}/photos", [
                'photo' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'photo_url',
                ],
            ]);

        // Verify file was stored
        $photoUrl = $response->json('data.photo_url');
        $this->assertTrue(Storage::disk('public')->exists($photoUrl));
    }

    /** @test */
    public function it_can_activate_invitation()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/digital-invitations/{$this->invitation->id}/activate");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'public_url',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('digital_invitations', [
            'id' => $this->invitation->id,
            'status' => DigitalInvitation::STATUS_ACTIVE,
        ]);
    }

    /** @test */
    public function it_can_view_public_invitation()
    {
        // First activate the invitation
        $this->invitation->update([
            'status' => DigitalInvitation::STATUS_ACTIVE,
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->getJson("/api/v1/invitations/{$this->invitation->slug}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'template',
                    'customization',
                    'view_count',
                    'slug',
                ],
            ]);
    }

    /** @test */
    public function it_cannot_view_expired_invitation()
    {
        $this->invitation->update([
            'status' => DigitalInvitation::STATUS_EXPIRED,
        ]);

        $response = $this->getJson("/api/v1/invitations/{$this->invitation->slug}");

        $response->assertStatus(404)
            ->assertJsonFragment([
                'message' => 'Invitation not found or has expired',
            ]);
    }

    /** @test */
    public function it_cannot_access_other_users_invitation()
    {
        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser, 'sanctum')
            ->getJson("/api/v1/digital-invitations/{$this->invitation->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function it_validates_customization_data()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/digital-invitations/{$this->invitation->id}/customize", [
                'event_date' => 'invalid-date',
                'venue_map_url' => 'not-a-url',
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors',
            ]);
    }
}

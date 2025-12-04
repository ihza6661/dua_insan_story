<?php

namespace Tests\Feature\Api\Admin;

use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PromoCodeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    #[Test]
    public function it_lists_all_promo_codes(): void
    {
        PromoCode::factory()->count(10)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/promo-codes');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'code',
                            'discount_type',
                            'discount_value',
                            'is_active',
                            'valid_from',
                            'valid_until',
                        ],
                    ],
                    'current_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonCount(10, 'data.data');
    }

    #[Test]
    public function it_filters_promo_codes_by_active_status(): void
    {
        PromoCode::factory()->count(5)->create(['is_active' => true]);
        PromoCode::factory()->count(3)->inactive()->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/promo-codes?status=active');

        $response->assertOk()
            ->assertJsonCount(5, 'data.data');
    }

    #[Test]
    public function it_filters_promo_codes_by_inactive_status(): void
    {
        PromoCode::factory()->count(5)->create(['is_active' => true]);
        PromoCode::factory()->count(3)->inactive()->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/promo-codes?status=inactive');

        $response->assertOk()
            ->assertJsonCount(3, 'data.data');
    }

    #[Test]
    public function it_searches_promo_codes_by_code(): void
    {
        PromoCode::factory()->create(['code' => 'SUMMER2025']);
        PromoCode::factory()->create(['code' => 'WINTER2025']);
        PromoCode::factory()->create(['code' => 'SPRING2025']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/promo-codes?search=SUMMER');

        $response->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.code', 'SUMMER2025');
    }

    #[Test]
    public function it_paginates_promo_codes(): void
    {
        PromoCode::factory()->count(20)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/promo-codes?per_page=10');

        $response->assertOk()
            ->assertJsonCount(10, 'data.data')
            ->assertJsonPath('data.total', 20)
            ->assertJsonPath('data.per_page', 10);
    }

    #[Test]
    public function it_creates_a_percentage_promo_code(): void
    {
        $data = [
            'code' => 'NEWYEAR2025',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'min_purchase' => 100000,
            'max_discount' => null,
            'usage_limit' => 100,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/promo-codes', $data);

        $response->assertCreated()
            ->assertJson([
                'message' => 'Promo code created successfully',
                'data' => [
                    'code' => 'NEWYEAR2025',
                    'discount_type' => 'percentage',
                    'discount_value' => 15,
                ],
            ]);

        $this->assertDatabaseHas('promo_codes', [
            'code' => 'NEWYEAR2025',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'used_count' => 0,
        ]);
    }

    #[Test]
    public function it_creates_a_fixed_promo_code(): void
    {
        $data = [
            'code' => 'FIXED50K',
            'discount_type' => 'fixed',
            'discount_value' => 50000,
            'min_purchase' => 200000,
            'max_discount' => null,
            'usage_limit' => 50,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addDays(15)->toDateString(),
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/promo-codes', $data);

        $response->assertCreated()
            ->assertJsonPath('data.code', 'FIXED50K')
            ->assertJsonPath('data.discount_type', 'fixed');
        
        // discount_value may be string or numeric depending on database driver
        $this->assertEquals(50000, (float) $response->json('data.discount_value'));
    }

    #[Test]
    public function it_converts_code_to_uppercase_when_creating(): void
    {
        $data = [
            'code' => 'lowercase2025',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/promo-codes', $data);

        $response->assertCreated()
            ->assertJsonPath('data.code', 'LOWERCASE2025');
    }

    #[Test]
    public function it_validates_required_fields_when_creating(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/promo-codes', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'discount_type', 'discount_value', 'valid_from', 'valid_until']);
    }

    #[Test]
    public function it_rejects_duplicate_promo_code(): void
    {
        PromoCode::factory()->create(['code' => 'DUPLICATE']);

        $data = [
            'code' => 'DUPLICATE',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/promo-codes', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    #[Test]
    public function it_rejects_percentage_over_100(): void
    {
        $data = [
            'code' => 'INVALID',
            'discount_type' => 'percentage',
            'discount_value' => 150,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/promo-codes', $data);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Validation failed',
                'errors' => [
                    'discount_value' => ['Percentage discount cannot exceed 100%'],
                ],
            ]);
    }

    #[Test]
    public function it_rejects_valid_until_before_valid_from(): void
    {
        $data = [
            'code' => 'INVALID',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'valid_from' => now()->addDays(10)->toDateString(),
            'valid_until' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/admin/promo-codes', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['valid_until']);
    }

    #[Test]
    public function it_shows_a_single_promo_code(): void
    {
        $promoCode = PromoCode::factory()->create(['code' => 'SHOW123']);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/promo-codes/{$promoCode->id}");

        $response->assertOk()
            ->assertJson([
                'message' => 'Promo code retrieved successfully',
                'data' => [
                    'id' => $promoCode->id,
                    'code' => 'SHOW123',
                ],
            ]);
    }

    #[Test]
    public function it_loads_usages_when_showing_promo_code(): void
    {
        $promoCode = PromoCode::factory()->create();
        $user = User::factory()->create(['role' => 'customer']);
        $order = \App\Models\Order::factory()->create(['customer_id' => $user->id]);
        
        PromoCodeUsage::factory()->create([
            'promo_code_id' => $promoCode->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/admin/promo-codes/{$promoCode->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'usages' => [
                        '*' => ['id', 'user'],
                    ],
                ],
            ]);
    }

    #[Test]
    public function it_updates_a_promo_code(): void
    {
        $promoCode = PromoCode::factory()->create([
            'code' => 'OLDCODE',
            'discount_value' => 10,
        ]);

        $data = [
            'code' => 'NEWCODE',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
        ];

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/promo-codes/{$promoCode->id}", $data);

        $response->assertOk()
            ->assertJson([
                'message' => 'Promo code updated successfully',
                'data' => [
                    'code' => 'NEWCODE',
                    'discount_value' => 20,
                ],
            ]);

        $this->assertDatabaseHas('promo_codes', [
            'id' => $promoCode->id,
            'code' => 'NEWCODE',
            'discount_value' => 20,
        ]);
    }

    #[Test]
    public function it_allows_same_code_when_updating_own_promo(): void
    {
        $promoCode = PromoCode::factory()->create(['code' => 'UNCHANGED']);

        $data = [
            'code' => 'UNCHANGED',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
        ];

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/promo-codes/{$promoCode->id}", $data);

        $response->assertOk();
    }

    #[Test]
    public function it_rejects_duplicate_code_when_updating(): void
    {
        PromoCode::factory()->create(['code' => 'EXISTING']);
        $promoCode2 = PromoCode::factory()->create(['code' => 'TOUPDATE']);

        $data = [
            'code' => 'EXISTING',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
        ];

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/admin/promo-codes/{$promoCode2->id}", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    #[Test]
    public function it_deletes_unused_promo_code(): void
    {
        $promoCode = PromoCode::factory()->create(['used_count' => 0]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/promo-codes/{$promoCode->id}");

        $response->assertOk()
            ->assertJson([
                'message' => 'Promo code deleted successfully',
            ]);

        $this->assertDatabaseMissing('promo_codes', ['id' => $promoCode->id]);
    }

    #[Test]
    public function it_prevents_deleting_used_promo_code(): void
    {
        $promoCode = PromoCode::factory()->create(['used_count' => 5]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/admin/promo-codes/{$promoCode->id}");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot delete promo code that has been used. Consider deactivating it instead.',
            ]);

        $this->assertDatabaseHas('promo_codes', ['id' => $promoCode->id]);
    }

    #[Test]
    public function it_gets_promo_code_statistics(): void
    {
        PromoCode::factory()->count(8)->create(['is_active' => true]);
        PromoCode::factory()->count(5)->inactive()->create();
        PromoCode::factory()->create(['used_count' => 50, 'is_active' => true]);
        PromoCode::factory()->create(['used_count' => 30, 'is_active' => true]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/promo-codes/statistics');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'total_promo_codes',
                    'active_promo_codes',
                    'total_usages',
                    'most_used_promo_codes',
                ],
            ]);
        
        // Verify we have statistics data
        $this->assertGreaterThanOrEqual(15, $response->json('data.total_promo_codes'));
        $this->assertGreaterThanOrEqual(10, $response->json('data.active_promo_codes'));
        $this->assertGreaterThanOrEqual(80, $response->json('data.total_usages')); // 50 + 30
    }

    #[Test]
    public function it_toggles_promo_code_status_from_active_to_inactive(): void
    {
        $promoCode = PromoCode::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/promo-codes/{$promoCode->id}/toggle-status");

        $response->assertOk()
            ->assertJson([
                'message' => 'Promo code status updated successfully',
                'data' => [
                    'is_active' => false,
                ],
            ]);

        $this->assertDatabaseHas('promo_codes', [
            'id' => $promoCode->id,
            'is_active' => false,
        ]);
    }

    #[Test]
    public function it_toggles_promo_code_status_from_inactive_to_active(): void
    {
        $promoCode = PromoCode::factory()->inactive()->create();

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/promo-codes/{$promoCode->id}/toggle-status");

        $response->assertOk()
            ->assertJsonPath('data.is_active', true);
    }

    #[Test]
    public function it_requires_admin_authentication_for_listing(): void
    {
        /** @var User $customer */
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)
            ->getJson('/api/v1/admin/promo-codes');

        $response->assertForbidden();
    }

    #[Test]
    public function it_requires_admin_authentication_for_creating(): void
    {
        /** @var User $customer */
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)
            ->postJson('/api/v1/admin/promo-codes', []);

        $response->assertForbidden();
    }

    #[Test]
    public function it_requires_admin_authentication_for_updating(): void
    {
        /** @var User $customer */
        $customer = User::factory()->create(['role' => 'customer']);
        $promoCode = PromoCode::factory()->create();

        $response = $this->actingAs($customer)
            ->putJson("/api/v1/admin/promo-codes/{$promoCode->id}", []);

        $response->assertForbidden();
    }

    #[Test]
    public function it_requires_admin_authentication_for_deleting(): void
    {
        /** @var User $customer */
        $customer = User::factory()->create(['role' => 'customer']);
        $promoCode = PromoCode::factory()->create();

        $response = $this->actingAs($customer)
            ->deleteJson("/api/v1/admin/promo-codes/{$promoCode->id}");

        $response->assertForbidden();
    }

    #[Test]
    public function guest_cannot_access_admin_endpoints(): void
    {
        $response = $this->getJson('/api/v1/admin/promo-codes');

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_returns_promo_codes_in_descending_order(): void
    {
        $old = PromoCode::factory()->create(['created_at' => now()->subDays(5)]);
        $recent = PromoCode::factory()->create(['created_at' => now()]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/promo-codes');

        $response->assertOk();

        $codes = $response->json('data.data');
        $this->assertEquals($recent->id, $codes[0]['id']);
        $this->assertEquals($old->id, $codes[1]['id']);
    }
}

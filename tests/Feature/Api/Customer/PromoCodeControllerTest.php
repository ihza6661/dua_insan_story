<?php

namespace Tests\Feature\Api\Customer;

use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PromoCodeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->create(['role' => 'customer']);
    }

    #[Test]
    public function it_validates_a_valid_percentage_promo_code(): void
    {
        PromoCode::factory()->percentage(15)->create([
            'code' => 'VALID15',
            'min_purchase_amount' => 100000,
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/promo-codes/validate', [
                'code' => 'VALID15',
                'subtotal' => 200000,
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'valid' => true,
                    'discount_amount' => 30000, // 15% of 200000
                ],
            ])
            ->assertJsonStructure([
                'message',
                'data' => [
                    'valid',
                    'discount_amount',
                    'code_details',
                ],
            ]);
    }

    #[Test]
    public function it_validates_a_valid_fixed_promo_code(): void
    {
        PromoCode::factory()->fixed(50000)->create([
            'code' => 'FIXED50K',
            'min_purchase_amount' => 100000,
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/promo-codes/validate', [
                'code' => 'FIXED50K',
                'subtotal' => 200000,
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'valid' => true,
                    'discount_amount' => 50000,
                ],
            ]);
    }

    #[Test]
    public function it_handles_case_insensitive_promo_codes(): void
    {
        PromoCode::factory()->percentage(10)->create([
            'code' => 'UPPERCASE',
            'min_purchase_amount' => 0,
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/promo-codes/validate', [
                'code' => 'uppercase',  // lowercase version of UPPERCASE
                'subtotal' => 100000,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.code_details.code', 'UPPERCASE');  // Should return the stored uppercase version
    }

    #[Test]
    public function it_rejects_non_existent_promo_code(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/promo-codes/validate', [
                'code' => 'NONEXISTENT',
                'subtotal' => 100000,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'data' => [
                    'valid' => false,
                    'discount_amount' => 0,
                ],
            ]);
    }

    #[Test]
    public function it_rejects_inactive_promo_code(): void
    {
        PromoCode::factory()->inactive()->create([
            'code' => 'INACTIVE',
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/promo-codes/validate', [
                'code' => 'INACTIVE',
                'subtotal' => 100000,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.valid', false);
    }

    #[Test]
    public function it_rejects_expired_promo_code(): void
    {
        PromoCode::factory()->expired()->create([
            'code' => 'EXPIRED',
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/promo-codes/validate', [
                'code' => 'EXPIRED',
                'subtotal' => 100000,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.valid', false);
    }

    #[Test]
    public function it_rejects_promo_code_below_minimum_purchase(): void
    {
        PromoCode::factory()->percentage(10)->create([
            'code' => 'MINPURCHASE',
            'min_purchase_amount' => 200000,
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/promo-codes/validate', [
                'code' => 'MINPURCHASE',
                'subtotal' => 100000, // Below minimum
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.valid', false);
    }

    #[Test]
    public function it_rejects_promo_code_when_usage_limit_exceeded(): void
    {
        $promoCode = PromoCode::factory()->create([
            'code' => 'LIMITED',
            'usage_limit_per_user' => 1,
        ]);

        // Create a usage record for this user
        $order = \App\Models\Order::factory()->create(['customer_id' => $this->customer->id]);
        PromoCodeUsage::factory()->create([
            'promo_code_id' => $promoCode->id,
            'user_id' => $this->customer->id,
            'order_id' => $order->id,
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/promo-codes/validate', [
                'code' => 'LIMITED',
                'subtotal' => 100000,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.valid', false);
    }

    #[Test]
    public function it_validates_required_fields(): void
    {
        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/promo-codes/validate', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'subtotal']);
    }

    #[Test]
    public function it_validates_subtotal_is_numeric(): void
    {
        PromoCode::factory()->create(['code' => 'TEST']);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/promo-codes/validate', [
                'code' => 'TEST',
                'subtotal' => 'not-a-number',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subtotal']);
    }

    #[Test]
    public function it_validates_subtotal_is_positive(): void
    {
        PromoCode::factory()->create(['code' => 'TEST']);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/promo-codes/validate', [
                'code' => 'TEST',
                'subtotal' => -100,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subtotal']);
    }

    #[Test]
    public function it_gets_active_promo_codes_without_authentication(): void
    {
        PromoCode::factory()->count(5)->create(['is_active' => true]);
        PromoCode::factory()->count(3)->inactive()->create();

        $response = $this->getJson('/api/v1/promo-codes/active');

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'description',
                        'discount_type',
                        'discount_value',
                        'min_purchase_amount',
                        'valid_until',
                    ],
                ],
            ]);

        // Should only return active promo codes
        $this->assertGreaterThanOrEqual(5, count($response->json('data')));
    }

    #[Test]
    public function it_requires_authentication_for_validation(): void
    {
        $response = $this->postJson('/api/v1/promo-codes/validate', [
            'code' => 'TEST',
            'subtotal' => 100000,
        ]);

        $response->assertUnauthorized();
    }

    #[Test]
    public function it_returns_code_details_on_successful_validation(): void
    {
        PromoCode::factory()->percentage(20)->create([
            'code' => 'DETAILS',
            'min_purchase_amount' => 50000,
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/promo-codes/validate', [
                'code' => 'DETAILS',
                'subtotal' => 100000,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'code_details' => [
                        'code',
                        'discount_type',
                        'discount_value',
                    ],
                ],
            ]);
    }

    #[Test]
    public function it_caps_fixed_discount_at_subtotal(): void
    {
        PromoCode::factory()->fixed(100000)->create([
            'code' => 'BIGDISCOUNT',
            'min_purchase_amount' => 0, // No minimum
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/promo-codes/validate', [
                'code' => 'BIGDISCOUNT',
                'subtotal' => 50000, // Less than discount
            ]);

        $response->assertOk()
            ->assertJsonPath('data.discount_amount', 50000); // Should be capped at subtotal
    }

    #[Test]
    public function it_applies_max_discount_to_percentage_codes(): void
    {
        PromoCode::factory()->percentage(50)->create([
            'code' => 'MAXDISCOUNT',
            'max_discount_amount' => 100000,
        ]);

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/promo-codes/validate', [
                'code' => 'MAXDISCOUNT',
                'subtotal' => 500000, // 50% would be 250000
            ]);

        $response->assertOk()
            ->assertJsonPath('data.discount_amount', 100000); // Should be capped at max_discount
    }

    #[Test]
    public function active_endpoint_only_returns_currently_valid_codes(): void
    {
        // Create valid promo codes
        PromoCode::factory()->count(3)->create([
            'is_active' => true,
            'valid_from' => now()->subDays(5),
            'valid_until' => now()->addDays(10),
        ]);

        // Create expired promo code
        PromoCode::factory()->expired()->create();

        // Create inactive promo code
        PromoCode::factory()->inactive()->create();

        $response = $this->getJson('/api/v1/promo-codes/active');

        $response->assertOk();
        
        $codes = $response->json('data');
        
        // Should only return the 3 valid promo codes (not expired or inactive)
        $this->assertCount(3, $codes);
        
        // All returned codes should have required fields
        foreach ($codes as $code) {
            $this->assertArrayHasKey('code', $code);
            $this->assertArrayHasKey('discount_type', $code);
            $this->assertArrayHasKey('discount_value', $code);
        }
    }
}

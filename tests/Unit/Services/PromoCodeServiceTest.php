<?php

namespace Tests\Unit\Services;

use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\User;
use App\Services\PromoCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PromoCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PromoCodeService $service;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PromoCodeService;
        $this->user = User::factory()->create(['role' => 'customer']);
    }

    #[Test]
    public function it_validates_a_valid_percentage_promo_code(): void
    {
        PromoCode::factory()->create([
            'code' => 'TEST15',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDay(),
            'min_purchase_amount' => 100000,
            'usage_limit_per_user' => 1,
        ]);

        $result = $this->service->validatePromoCode('TEST15', $this->user, 200000);

        $this->assertTrue($result['valid']);
        $this->assertEquals(30000, $result['discount']); // 15% of 200000
        $this->assertEquals('Kode promo berhasil diterapkan!', $result['message']);
        $this->assertNotNull($result['code_details']);
        $this->assertEquals('TEST15', $result['code_details']['code']);
    }

    #[Test]
    public function it_validates_a_valid_fixed_promo_code(): void
    {
        PromoCode::factory()->create([
            'code' => 'SAVE50K',
            'discount_type' => 'fixed',
            'discount_value' => 50000,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDay(),
            'min_purchase_amount' => 100000,
            'usage_limit_per_user' => 1,
        ]);

        $result = $this->service->validatePromoCode('SAVE50K', $this->user, 200000);

        $this->assertTrue($result['valid']);
        $this->assertEquals(50000, $result['discount']);
        $this->assertEquals('Kode promo berhasil diterapkan!', $result['message']);
    }

    #[Test]
    public function it_rejects_non_existent_promo_code(): void
    {
        $result = $this->service->validatePromoCode('INVALID', $this->user, 200000);

        $this->assertFalse($result['valid']);
        $this->assertEquals(0, $result['discount']);
        $this->assertEquals('Kode promo tidak ditemukan.', $result['message']);
        $this->assertNull($result['code_details']);
    }

    #[Test]
    public function it_rejects_inactive_promo_code(): void
    {
        PromoCode::factory()->create([
            'code' => 'INACTIVE',
            'is_active' => false,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDay(),
        ]);

        $result = $this->service->validatePromoCode('INACTIVE', $this->user, 200000);

        $this->assertFalse($result['valid']);
        $this->assertEquals('Kode promo tidak aktif.', $result['message']);
    }

    #[Test]
    public function it_rejects_expired_promo_code(): void
    {
        PromoCode::factory()->create([
            'code' => 'EXPIRED',
            'is_active' => true,
            'valid_from' => now()->subDays(10),
            'valid_until' => now()->subDay(),
        ]);

        $result = $this->service->validatePromoCode('EXPIRED', $this->user, 200000);

        $this->assertFalse($result['valid']);
        $this->assertEquals('Kode promo sudah kadaluarsa atau tidak valid.', $result['message']);
    }

    #[Test]
    public function it_rejects_promo_code_below_min_purchase_amount(): void
    {
        PromoCode::factory()->create([
            'code' => 'MINPURCHASE',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDay(),
            'min_purchase_amount' => 500000,
        ]);

        $result = $this->service->validatePromoCode('MINPURCHASE', $this->user, 200000);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Pembelian minimum', $result['message']);
    }

    #[Test]
    public function it_rejects_promo_code_when_user_usage_limit_exceeded(): void
    {
        $promoCode = PromoCode::factory()->create([
            'code' => 'ONCEONLY',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDay(),
            'usage_limit_per_user' => 1,
        ]);

        // Create order and usage record
        $order = \App\Models\Order::factory()->create(['customer_id' => $this->user->id]);
        PromoCodeUsage::factory()->create([
            'promo_code_id' => $promoCode->id,
            'user_id' => $this->user->id,
            'order_id' => $order->id,
        ]);

        $result = $this->service->validatePromoCode('ONCEONLY', $this->user, 200000);

        $this->assertFalse($result['valid']);
        $this->assertEquals('Anda sudah menggunakan kode promo ini.', $result['message']);
    }

    #[Test]
    public function it_applies_promo_code_and_records_usage(): void
    {
        $promoCode = PromoCode::factory()->create([
            'code' => 'APPLY',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDay(),
        ]);

        // Create an order for testing
        $order = \App\Models\Order::factory()->create(['customer_id' => $this->user->id]);
        $discountAmount = 20000;

        $usage = $this->service->applyPromoCode($order->id, $promoCode->id, $this->user->id, $discountAmount);

        $this->assertInstanceOf(PromoCodeUsage::class, $usage);
        $this->assertEquals($promoCode->id, $usage->promo_code_id);
        $this->assertEquals($this->user->id, $usage->user_id);
        $this->assertEquals($order->id, $usage->order_id);
        $this->assertEquals($discountAmount, $usage->discount_amount);

        // Check usage was recorded in database
        $this->assertDatabaseHas('promo_code_usages', [
            'promo_code_id' => $promoCode->id,
            'user_id' => $this->user->id,
            'order_id' => $order->id,
            'discount_amount' => $discountAmount,
        ]);
    }

    #[Test]
    public function it_gets_active_promo_codes(): void
    {
        // Create active promo codes
        PromoCode::factory()->create([
            'code' => 'ACTIVE1',
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDay(),
        ]);

        PromoCode::factory()->create([
            'code' => 'ACTIVE2',
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDay(),
        ]);

        // Create inactive promo code (should not be returned)
        PromoCode::factory()->create([
            'code' => 'INACTIVE',
            'is_active' => false,
        ]);

        $activeCodes = $this->service->getActivePromoCodes();

        $this->assertCount(2, $activeCodes);
        $this->assertEquals('ACTIVE1', $activeCodes[0]['code']);
        $this->assertEquals('ACTIVE2', $activeCodes[1]['code']);
    }

    #[Test]
    public function it_handles_case_insensitive_promo_codes(): void
    {
        PromoCode::factory()->create([
            'code' => 'LOWERCASE',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDay(),
        ]);

        // Try with lowercase
        $result = $this->service->validatePromoCode('lowercase', $this->user, 200000);

        $this->assertTrue($result['valid']);
    }

    #[Test]
    public function it_caps_fixed_discount_to_subtotal(): void
    {
        PromoCode::factory()->create([
            'code' => 'BIGDISCOUNT',
            'discount_type' => 'fixed',
            'discount_value' => 500000, // Larger than subtotal
            'is_active' => true,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDay(),
        ]);

        $result = $this->service->validatePromoCode('BIGDISCOUNT', $this->user, 200000);

        $this->assertTrue($result['valid']);
        $this->assertEquals(200000, $result['discount']); // Should cap at subtotal
    }

    #[Test]
    public function it_gets_promo_code_by_code_string(): void
    {
        $promoCode = PromoCode::factory()->create(['code' => 'FINDME']);

        $found = $this->service->getPromoCodeByCode('FINDME');

        $this->assertNotNull($found);
        $this->assertEquals($promoCode->id, $found->id);
    }

    #[Test]
    public function it_checks_if_user_has_used_promo_code(): void
    {
        $promoCode = PromoCode::factory()->create(['code' => 'USED']);

        // User hasn't used it yet
        $this->assertFalse($this->service->hasUserUsedPromoCode($this->user, $promoCode));

        // Create order and usage record
        $order = \App\Models\Order::factory()->create(['customer_id' => $this->user->id]);
        PromoCodeUsage::factory()->create([
            'promo_code_id' => $promoCode->id,
            'user_id' => $this->user->id,
            'order_id' => $order->id,
        ]);

        // Now user has used it
        $this->assertTrue($this->service->hasUserUsedPromoCode($this->user, $promoCode));
    }
}

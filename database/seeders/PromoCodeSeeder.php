<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PromoCode;
use Illuminate\Support\Facades\DB;

class PromoCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing promo codes
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('promo_codes')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $promoCodes = [
            [
                'code' => 'WEDDING2025',
                'description' => 'Diskon spesial tahun baru 2025 untuk semua produk undangan pernikahan',
                'discount_type' => 'percentage',
                'discount_value' => 15.00,
                'min_purchase_amount' => 100000.00,
                'max_discount_amount' => 100000.00,
                'usage_limit_per_user' => 1,
                'total_usage_limit' => 100,
                'used_count' => 12,
                'valid_from' => now()->subDays(30),
                'valid_until' => now()->addMonths(2),
                'is_active' => true,
            ],
            [
                'code' => 'SAVE50K',
                'description' => 'Hemat Rp 50.000 untuk pembelian undangan cetak',
                'discount_type' => 'fixed',
                'discount_value' => 50000.00,
                'min_purchase_amount' => 200000.00,
                'max_discount_amount' => null,
                'usage_limit_per_user' => 1,
                'total_usage_limit' => 50,
                'used_count' => 8,
                'valid_from' => now()->subDays(15),
                'valid_until' => now()->addMonth(),
                'is_active' => true,
            ],
            [
                'code' => 'NEWCUSTOMER',
                'description' => 'Diskon 20% untuk pelanggan baru',
                'discount_type' => 'percentage',
                'discount_value' => 20.00,
                'min_purchase_amount' => 150000.00,
                'max_discount_amount' => 75000.00,
                'usage_limit_per_user' => 1,
                'total_usage_limit' => 200,
                'used_count' => 45,
                'valid_from' => now()->subDays(60),
                'valid_until' => now()->addMonths(3),
                'is_active' => true,
            ],
            [
                'code' => 'FLASH10',
                'description' => 'Flash sale 10% untuk semua produk',
                'discount_type' => 'percentage',
                'discount_value' => 10.00,
                'min_purchase_amount' => 50000.00,
                'max_discount_amount' => 50000.00,
                'usage_limit_per_user' => 1,
                'total_usage_limit' => 500,
                'used_count' => 234,
                'valid_from' => now()->subDays(7),
                'valid_until' => now()->addDays(7),
                'is_active' => true,
            ],
            [
                'code' => 'PREMIUM100K',
                'description' => 'Diskon Rp 100.000 untuk paket premium',
                'discount_type' => 'fixed',
                'discount_value' => 100000.00,
                'min_purchase_amount' => 500000.00,
                'max_discount_amount' => null,
                'usage_limit_per_user' => 1,
                'total_usage_limit' => 30,
                'used_count' => 5,
                'valid_from' => now()->subDays(5),
                'valid_until' => now()->addMonths(1),
                'is_active' => true,
            ],
            [
                'code' => 'EXPIRED2024',
                'description' => 'Kode promo kadaluarsa - testing purposes',
                'discount_type' => 'percentage',
                'discount_value' => 25.00,
                'min_purchase_amount' => 100000.00,
                'max_discount_amount' => 150000.00,
                'usage_limit_per_user' => 1,
                'total_usage_limit' => 100,
                'used_count' => 67,
                'valid_from' => now()->subMonths(3),
                'valid_until' => now()->subDays(10),
                'is_active' => false,
            ],
            [
                'code' => 'LIMITREACHED',
                'description' => 'Kode promo limit tercapai - testing purposes',
                'discount_type' => 'fixed',
                'discount_value' => 30000.00,
                'min_purchase_amount' => 100000.00,
                'max_discount_amount' => null,
                'usage_limit_per_user' => 1,
                'total_usage_limit' => 10,
                'used_count' => 10,
                'valid_from' => now()->subDays(20),
                'valid_until' => now()->addDays(10),
                'is_active' => true,
            ],
            [
                'code' => 'INACTIVE50',
                'description' => 'Kode promo non-aktif - testing purposes',
                'discount_type' => 'percentage',
                'discount_value' => 50.00,
                'min_purchase_amount' => 0.00,
                'max_discount_amount' => 200000.00,
                'usage_limit_per_user' => 1,
                'total_usage_limit' => 100,
                'used_count' => 0,
                'valid_from' => now()->subDays(1),
                'valid_until' => now()->addMonths(6),
                'is_active' => false,
            ],
        ];

        foreach ($promoCodes as $promoCode) {
            PromoCode::create($promoCode);
            echo "✓ Created promo code: {$promoCode['code']} ({$promoCode['discount_type']}: " . 
                 ($promoCode['discount_type'] === 'percentage' ? "{$promoCode['discount_value']}%" : "Rp " . number_format($promoCode['discount_value'], 0, ',', '.')) . 
                 ")\n";
        }

        echo "\n✅ Successfully created " . count($promoCodes) . " promo codes!\n";
        echo "   - Active: " . collect($promoCodes)->where('is_active', true)->count() . "\n";
        echo "   - Inactive: " . collect($promoCodes)->where('is_active', false)->count() . "\n";
    }
}

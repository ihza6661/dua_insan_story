<?php

namespace App\Services;

use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\User;
use Illuminate\Support\Collection;

class PromoCodeService
{
    /**
     * Validate promo code for a user and subtotal
     *
     * @return array{valid: bool, discount: float, message: string, code_details: ?array}
     */
    public function validatePromoCode(string $code, User $user, float $subtotal): array
    {
        // Find promo code
        $promoCode = PromoCode::where('code', strtoupper($code))->first();

        if (! $promoCode) {
            return [
                'valid' => false,
                'discount' => 0,
                'message' => 'Kode promo tidak ditemukan.',
                'code_details' => null,
            ];
        }

        // Check if promo code can be used by this user
        if (! $promoCode->canBeUsedBy($user)) {
            // Provide specific error message
            if (! $promoCode->is_active) {
                $message = 'Kode promo tidak aktif.';
            } elseif (! $promoCode->isValid()) {
                $message = 'Kode promo sudah kadaluarsa atau tidak valid.';
            } else {
                $userUsageCount = $promoCode->usages()->where('user_id', $user->id)->count();
                if ($userUsageCount >= $promoCode->usage_limit_per_user) {
                    $message = 'Anda sudah menggunakan kode promo ini.';
                } else {
                    $message = 'Kode promo tidak dapat digunakan.';
                }
            }

            return [
                'valid' => false,
                'discount' => 0,
                'message' => $message,
                'code_details' => null,
            ];
        }

        // Calculate discount
        $discount = $promoCode->calculateDiscount($subtotal);

        if ($discount <= 0) {
            $minPurchase = $promoCode->min_purchase_amount;
            $message = $minPurchase
                ? "Pembelian minimum Rp ".number_format($minPurchase, 0, ',', '.').' diperlukan untuk kode promo ini.'
                : 'Kode promo tidak dapat diterapkan pada pesanan ini.';

            return [
                'valid' => false,
                'discount' => 0,
                'message' => $message,
                'code_details' => null,
            ];
        }

        // Return success with discount details
        return [
            'valid' => true,
            'discount' => $discount,
            'message' => 'Kode promo berhasil diterapkan!',
            'code_details' => [
                'id' => $promoCode->id,
                'code' => $promoCode->code,
                'discount_type' => $promoCode->discount_type,
                'discount_value' => $promoCode->discount_value,
                'description' => $promoCode->description,
            ],
        ];
    }

    /**
     * Apply promo code to an order (create usage record)
     */
    public function applyPromoCode(int $orderId, int $promoCodeId, int $userId, float $discountAmount): PromoCodeUsage
    {
        // Create usage record
        $usage = PromoCodeUsage::create([
            'promo_code_id' => $promoCodeId,
            'user_id' => $userId,
            'order_id' => $orderId,
            'discount_amount' => $discountAmount,
        ]);

        // Increment promo code usage count
        $promoCode = PromoCode::find($promoCodeId);
        $promoCode->incrementUsageCount();

        return $usage;
    }

    /**
     * Get active promo codes (public-facing)
     */
    public function getActivePromoCodes(): Collection
    {
        return PromoCode::active()
            ->valid()
            ->available()
            ->orderBy('created_at', 'desc')
            ->get(['id', 'code', 'description', 'discount_type', 'discount_value', 'min_purchase_amount', 'valid_until']);
    }

    /**
     * Get promo code by code string
     */
    public function getPromoCodeByCode(string $code): ?PromoCode
    {
        return PromoCode::where('code', strtoupper($code))->first();
    }

    /**
     * Check if user has already used a promo code
     */
    public function hasUserUsedPromoCode(User $user, PromoCode $promoCode): bool
    {
        return $promoCode->usages()->where('user_id', $user->id)->exists();
    }
}

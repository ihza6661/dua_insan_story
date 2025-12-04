<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromoCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'min_purchase_amount',
        'max_discount_amount',
        'usage_limit_per_user',
        'total_usage_limit',
        'used_count',
        'valid_from',
        'valid_until',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_purchase_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the orders that used this promo code
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the usage records for this promo code
     */
    public function usages(): HasMany
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    /**
     * Get the admin who created this promo code
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get only active promo codes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only valid promo codes (within date range)
     */
    public function scopeValid($query)
    {
        $now = now();

        return $query->where('valid_from', '<=', $now)
            ->where('valid_until', '>=', $now);
    }

    /**
     * Scope to get available promo codes (not exceeded total usage limit)
     */
    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('total_usage_limit')
                ->orWhereRaw('used_count < total_usage_limit');
        });
    }

    /**
     * Check if promo code can be used by a specific user
     */
    public function canBeUsedBy(User $user): bool
    {
        // Check if promo code is active
        if (! $this->is_active) {
            return false;
        }

        // Check if promo code is within valid date range
        $now = now();
        if ($now < $this->valid_from || $now > $this->valid_until) {
            return false;
        }

        // Check if total usage limit is reached
        if ($this->total_usage_limit !== null && $this->used_count >= $this->total_usage_limit) {
            return false;
        }

        // Check if user has already used this promo code
        $userUsageCount = $this->usages()->where('user_id', $user->id)->count();
        if ($userUsageCount >= $this->usage_limit_per_user) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discount amount for a given subtotal
     */
    public function calculateDiscount(float $subtotal): float
    {
        // Check minimum purchase requirement
        if ($this->min_purchase_amount !== null && $subtotal < $this->min_purchase_amount) {
            return 0;
        }

        $discount = 0;

        if ($this->discount_type === 'fixed') {
            $discount = $this->discount_value;
        } elseif ($this->discount_type === 'percentage') {
            $discount = ($subtotal * $this->discount_value) / 100;

            // Apply maximum discount cap if set
            if ($this->max_discount_amount !== null && $discount > $this->max_discount_amount) {
                $discount = $this->max_discount_amount;
            }
        }

        // Ensure discount doesn't exceed subtotal
        return min($discount, $subtotal);
    }

    /**
     * Check if promo code is valid (not considering user)
     */
    public function isValid(): bool
    {
        $now = now();

        return $this->is_active
            && $now >= $this->valid_from
            && $now <= $this->valid_until
            && ($this->total_usage_limit === null || $this->used_count < $this->total_usage_limit);
    }

    /**
     * Increment usage count
     */
    public function incrementUsageCount(): void
    {
        $this->increment('used_count');
    }
}

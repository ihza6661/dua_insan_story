<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AbandonedCart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'email',
        'name',
        'cart_items',
        'cart_total',
        'items_count',
        'recovery_token',
        'abandoned_at',
        'first_reminder_sent_at',
        'second_reminder_sent_at',
        'third_reminder_sent_at',
        'recovered_at',
        'recovered_order_id',
        'is_recovered',
        'recovery_source',
    ];

    protected $casts = [
        'cart_items' => 'array',
        'cart_total' => 'decimal:2',
        'abandoned_at' => 'datetime',
        'first_reminder_sent_at' => 'datetime',
        'second_reminder_sent_at' => 'datetime',
        'third_reminder_sent_at' => 'datetime',
        'recovered_at' => 'datetime',
        'is_recovered' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Generate recovery token on creation
        static::creating(function ($abandonedCart) {
            if (!$abandonedCart->recovery_token) {
                $abandonedCart->recovery_token = Str::random(64);
            }
        });
    }

    /**
     * User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Recovered order relationship
     */
    public function recoveredOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'recovered_order_id');
    }

    /**
     * Check if cart should receive first reminder (1 hour after abandonment)
     */
    public function shouldSendFirstReminder(): bool
    {
        return !$this->first_reminder_sent_at
            && !$this->is_recovered
            && $this->abandoned_at->diffInHours(now()) >= 1;
    }

    /**
     * Check if cart should receive second reminder (24 hours after abandonment)
     */
    public function shouldSendSecondReminder(): bool
    {
        return $this->first_reminder_sent_at
            && !$this->second_reminder_sent_at
            && !$this->is_recovered
            && $this->abandoned_at->diffInHours(now()) >= 24;
    }

    /**
     * Check if cart should receive third reminder (3 days after abandonment)
     */
    public function shouldSendThirdReminder(): bool
    {
        return $this->second_reminder_sent_at
            && !$this->third_reminder_sent_at
            && !$this->is_recovered
            && $this->abandoned_at->diffInDays(now()) >= 3;
    }

    /**
     * Mark cart as recovered
     */
    public function markAsRecovered(int $orderId, string $source = 'direct'): void
    {
        $this->update([
            'is_recovered' => true,
            'recovered_at' => now(),
            'recovered_order_id' => $orderId,
            'recovery_source' => $source,
        ]);
    }

    /**
     * Get recovery URL
     */
    public function getRecoveryUrl(): string
    {
        return config('app.frontend_url') . '/cart/recover/' . $this->recovery_token;
    }

    /**
     * Scope: Not recovered carts
     */
    public function scopeNotRecovered($query)
    {
        return $query->where('is_recovered', false);
    }

    /**
     * Scope: Recovered carts
     */
    public function scopeRecovered($query)
    {
        return $query->where('is_recovered', true);
    }

    /**
     * Scope: Pending first reminder
     */
    public function scopePendingFirstReminder($query)
    {
        return $query->notRecovered()
            ->whereNull('first_reminder_sent_at')
            ->where('abandoned_at', '<=', now()->subHour());
    }

    /**
     * Scope: Pending second reminder
     */
    public function scopePendingSecondReminder($query)
    {
        return $query->notRecovered()
            ->whereNotNull('first_reminder_sent_at')
            ->whereNull('second_reminder_sent_at')
            ->where('abandoned_at', '<=', now()->subHours(24));
    }

    /**
     * Scope: Pending third reminder
     */
    public function scopePendingThirdReminder($query)
    {
        return $query->notRecovered()
            ->whereNotNull('second_reminder_sent_at')
            ->whereNull('third_reminder_sent_at')
            ->where('abandoned_at', '<=', now()->subDays(3));
    }
}

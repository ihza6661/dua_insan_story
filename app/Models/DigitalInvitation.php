<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class DigitalInvitation
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $order_id
 * @property int $template_id
 * @property string $slug
 * @property string $status
 * @property \Carbon\Carbon|null $activated_at
 * @property \Carbon\Carbon|null $expires_at
 * @property int $view_count
 * @property \Carbon\Carbon|null $last_viewed_at
 */
class DigitalInvitation extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'order_id',
        'template_id',
        'slug',
        'status',
        'activated_at',
        'expires_at',
        'view_count',
        'last_viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'view_count' => 'integer',
        ];
    }

    // ========== RELATIONSHIPS ==========

    /**
     * Get the user who owns this invitation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order that created this invitation.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the template used for this invitation.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(InvitationTemplate::class, 'template_id');
    }

    /**
     * Get the customization data for this invitation.
     */
    public function data(): HasOne
    {
        return $this->hasOne(DigitalInvitationData::class);
    }

    // ========== HELPER METHODS ==========

    /**
     * Check if invitation is active and not expired.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->expires_at
            && $this->expires_at->isFuture();
    }

    /**
     * Increment view count.
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
        $this->update(['last_viewed_at' => now()]);
    }

    /**
     * Get the public URL for this invitation.
     */
    public function getPublicUrlAttribute(): string
    {
        return config('app.url') . '/invite/' . $this->slug;
    }
}

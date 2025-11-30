<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DesignProof extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'uploaded_by',
        'version',
        'file_url',
        'file_name',
        'file_type',
        'file_size',
        'thumbnail_url',
        'status',
        'reviewed_at',
        'reviewed_by',
        'customer_feedback',
        'admin_notes',
        'customer_notified',
        'customer_notified_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'customer_notified_at' => 'datetime',
        'customer_notified' => 'boolean',
        'file_size' => 'integer',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REVISION_REQUESTED = 'revision_requested';

    public const STATUS_REJECTED = 'rejected';

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the full URL for the design proof file
     */
    public function getFullFileUrlAttribute(): string
    {
        if (filter_var($this->file_url, FILTER_VALIDATE_URL)) {
            return $this->file_url;
        }

        return Storage::url($this->file_url);
    }

    /**
     * Get the full URL for the thumbnail
     */
    public function getFullThumbnailUrlAttribute(): ?string
    {
        if (! $this->thumbnail_url) {
            return null;
        }
        if (filter_var($this->thumbnail_url, FILTER_VALIDATE_URL)) {
            return $this->thumbnail_url;
        }

        return Storage::url($this->thumbnail_url);
    }

    /**
     * Check if the design proof is pending approval
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the design proof is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if revision is requested
     */
    public function isRevisionRequested(): bool
    {
        return $this->status === self::STATUS_REVISION_REQUESTED;
    }

    /**
     * Check if the design proof is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Get human-readable file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (! $this->file_size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Scope to get only pending proofs
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get only approved proofs
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope to get proofs for a specific order
     */
    public function scopeForOrder($query, int $orderId)
    {
        return $query->whereHas('orderItem', function ($q) use ($orderId) {
            $q->where('order_id', $orderId);
        });
    }
}

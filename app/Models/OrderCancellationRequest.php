<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class OrderCancellationRequest
 *
 * @property int $id
 * @property int $order_id
 * @property int $requested_by
 * @property string $cancellation_reason
 * @property string $status
 * @property int|null $reviewed_by
 * @property string|null $reviewed_at
 * @property string|null $admin_notes
 * @property bool $refund_initiated
 * @property float|null $refund_amount
 * @property string|null $refund_transaction_id
 * @property string|null $refund_status
 * @property bool $stock_restored
 * @property string $created_at
 * @property string $updated_at
 */
class OrderCancellationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'requested_by',
        'cancellation_reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
        'refund_initiated',
        'refund_amount',
        'refund_transaction_id',
        'refund_status',
        'stock_restored',
    ];

    protected function casts(): array
    {
        return [
            'refund_initiated' => 'boolean',
            'stock_restored' => 'boolean',
            'refund_amount' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    // ========== CONSTANTS ==========

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const REFUND_STATUS_PENDING = 'pending';

    public const REFUND_STATUS_PROCESSING = 'processing';

    public const REFUND_STATUS_COMPLETED = 'completed';

    public const REFUND_STATUS_FAILED = 'failed';

    // ========== RELATIONSHIPS ==========

    /**
     * Get the order associated with this cancellation request.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who requested the cancellation.
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the admin who reviewed the cancellation request.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ========== HELPER METHODS ==========

    /**
     * Check if the cancellation request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the cancellation request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if the cancellation request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Mark the cancellation request as approved.
     */
    public function approve(User $admin, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
        ]);
    }

    /**
     * Mark the cancellation request as rejected.
     */
    public function reject(User $admin, string $notes): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'admin_notes' => $notes,
        ]);
    }
}

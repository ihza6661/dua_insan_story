<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int|null $customer_id
 * @property string $order_number
 * @property float $total_amount
 * @property string|null $shipping_address
 * @property string|null $order_status
 * @property string|null $snap_token
 */
class Order extends Model
{
    use HasFactory;

    // Valid order status constants
    public const STATUS_PENDING_PAYMENT = 'Pending Payment';

    public const STATUS_PARTIALLY_PAID = 'Partially Paid';

    public const STATUS_PAID = 'Paid';

    public const STATUS_PROCESSING = 'Processing';

    public const STATUS_DESIGN_APPROVAL = 'Design Approval';

    public const STATUS_IN_PRODUCTION = 'In Production';

    public const STATUS_SHIPPED = 'Shipped';

    public const STATUS_DELIVERED = 'Delivered';

    public const STATUS_COMPLETED = 'Completed';

    public const STATUS_CANCELLED = 'Cancelled';

    public const STATUS_FAILED = 'Failed';

    public const STATUS_REFUNDED = 'Refunded';

    /**
     * Get all valid order statuses.
     */
    public static function getValidStatuses(): array
    {
        return [
            self::STATUS_PENDING_PAYMENT,
            self::STATUS_PARTIALLY_PAID,
            self::STATUS_PAID,
            self::STATUS_PROCESSING,
            self::STATUS_DESIGN_APPROVAL,
            self::STATUS_IN_PRODUCTION,
            self::STATUS_SHIPPED,
            self::STATUS_DELIVERED,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_FAILED,
            self::STATUS_REFUNDED,
        ];
    }

    protected $fillable = [
        'customer_id',
        'order_number',
        'total_amount',
        'shipping_address',
        'shipping_cost',
        'shipping_method',
        'shipping_service',
        'courier',
        'tracking_number',
        'snap_token',
        // Note: order_status and payment_status removed from fillable for security
    ];

    /**
     * Fields that should NOT be mass-assigned.
     *
     * @var array
     */
    protected $guarded = [
        'order_status',
        'payment_status',
        'payment_gateway',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'shipping_cost' => 'float',
    ];

    /**
     * Get the customer who placed the order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get all payments for this order.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the invitation detail for this order.
     */
    public function invitationDetail(): HasOne
    {
        return $this->hasOne(InvitationDetail::class);
    }

    /**
     * Get the shipping address for this order.
     */
    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    /**
     * Get the billing address for this order.
     */
    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    /**
     * Get all items in this order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get all cancellation requests for this order.
     */
    public function cancellationRequests(): HasMany
    {
        return $this->hasMany(OrderCancellationRequest::class);
    }

    /**
     * Get the active cancellation request for this order.
     */
    public function activeCancellationRequest(): HasOne
    {
        return $this->hasOne(OrderCancellationRequest::class)
            ->where('status', OrderCancellationRequest::STATUS_PENDING)
            ->latestOfMany();
    }

    /**
     * Get remaining balance (computed from database).
     * REFACTORED: Avoid N+1 by using withSum() in queries instead.
     */
    public function getRemainingBalanceAttribute(): float
    {
        // Check if already loaded via withSum
        if (isset($this->attributes['paid_amount'])) {
            return $this->total_amount - $this->attributes['paid_amount'];
        }

        // Fallback: compute on-demand (triggers query)
        $paid = $this->payments()->where('status', 'paid')->sum('amount');

        return $this->total_amount - $paid;
    }

    /**
     * Get total amount paid (computed from database).
     * REFACTORED: Avoid N+1 by using withSum() in queries instead.
     */
    public function getAmountPaidAttribute(): float
    {
        // Check if already loaded via withSum
        if (isset($this->attributes['paid_amount'])) {
            return $this->attributes['paid_amount'];
        }

        // Fallback: compute on-demand (triggers query)
        return $this->payments()->where('status', 'paid')->sum('amount');
    }

    // ========== QUERY SCOPES ==========

    /**
     * Scope a query to only include pending orders.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('order_status', 'Pending Payment');
    }

    /**
     * Scope a query to only include paid orders.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('order_status', 'Paid');
    }

    /**
     * Scope a query to only include completed orders.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('order_status', 'Completed');
    }

    /**
     * Scope a query to filter by customer.
     */
    public function scopeByCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope to eager load payment totals (to avoid N+1).
     */
    public function scopeWithPaymentTotals(Builder $query): Builder
    {
        return $query->withSum(['payments as paid_amount' => function ($q) {
            $q->where('status', 'paid');
        }], 'amount');
    }
}

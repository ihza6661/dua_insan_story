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

    // Valid payment status constants
    public const PAYMENT_STATUS_PENDING = 'pending';

    public const PAYMENT_STATUS_PAID = 'paid';

    public const PAYMENT_STATUS_PARTIALLY_PAID = 'partially_paid';

    public const PAYMENT_STATUS_FAILED = 'failed';

    public const PAYMENT_STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_STATUS_REFUNDED = 'refunded';

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

    /**
     * Get all valid payment statuses.
     */
    public static function getValidPaymentStatuses(): array
    {
        return [
            self::PAYMENT_STATUS_PENDING,
            self::PAYMENT_STATUS_PAID,
            self::PAYMENT_STATUS_PARTIALLY_PAID,
            self::PAYMENT_STATUS_FAILED,
            self::PAYMENT_STATUS_CANCELLED,
            self::PAYMENT_STATUS_REFUNDED,
        ];
    }

    protected $fillable = [
        'customer_id',
        'promo_code_id',
        'order_number',
        'total_amount',
        'discount_amount',
        'subtotal_amount',
        'shipping_address',
        'shipping_cost',
        'shipping_method',
        'shipping_service',
        'courier',
        'tracking_number',
        'snap_token',
        'payment_option',
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
        'discount_amount' => 'float',
        'subtotal_amount' => 'float',
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
     * Get digital invitations created from this order.
     */
    public function digitalInvitations(): HasMany
    {
        return $this->hasMany(DigitalInvitation::class);
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
     * Get the promo code used for this order.
     */
    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
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
        $paid = $this->payments()->where('status', Payment::STATUS_PAID)->sum('amount');

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
        return $this->payments()->where('status', Payment::STATUS_PAID)->sum('amount');
    }

    /**
     * Get formatted payment method from the first successful payment.
     * Extracts payment method details from Midtrans raw_response.
     */
    public function getFormattedPaymentMethod(): ?string
    {
        $payment = $this->payments()
            ->whereIn('status', [Payment::STATUS_PAID])
            ->orderBy('created_at', 'asc')
            ->first();

        if (! $payment) {
            return null;
        }

        $method = $payment->payment_gateway;

        if (! empty($payment->raw_response) && isset($payment->raw_response['payment_type'])) {
            $type = $payment->raw_response['payment_type'];
            $method = ucwords(str_replace('_', ' ', $type));

            if ($type === 'bank_transfer') {
                if (isset($payment->raw_response['va_numbers'][0]['bank'])) {
                    $method .= ' - '.strtoupper($payment->raw_response['va_numbers'][0]['bank']);
                } elseif (isset($payment->raw_response['permata_va_number'])) {
                    $method .= ' - PERMATA';
                }
            } elseif ($type === 'cstore' && isset($payment->raw_response['store'])) {
                $method .= ' - '.ucfirst($payment->raw_response['store']);
            } elseif ($type === 'echannel' && isset($payment->raw_response['biller_code'])) {
                $method .= ' - Mandiri Bill Payment';
            } elseif ($type === 'gopay') {
                $method = 'GoPay';
            } elseif ($type === 'shopeepay') {
                $method = 'ShopeePay';
            } elseif ($type === 'qris') {
                $method = 'QRIS';
            }
        }

        return $method;
    }

    /**
     * Get formatted payment option label in Indonesian.
     */
    public function getFormattedPaymentOption(): string
    {
        return match ($this->payment_option) {
            'full' => 'Pembayaran Penuh',
            'dp' => 'Down Payment (DP 50%)',
            'final' => 'Pelunasan Akhir',
            default => ucfirst($this->payment_option ?? 'Tidak Diketahui'),
        };
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

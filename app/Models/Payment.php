<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    // Valid payment status constants
    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUNDED = 'refunded';

    // Valid payment type constants
    public const TYPE_FULL = 'full';

    public const TYPE_DOWN_PAYMENT = 'dp';

    public const TYPE_FINAL = 'final';

    protected $fillable = [
        'order_id',
        'transaction_id',
        'payment_gateway',
        'amount',
        'payment_type',
        'status',
        'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'raw_response' => 'array',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get all valid payment statuses.
     */
    public static function getValidStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PAID,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
            self::STATUS_REFUNDED,
        ];
    }

    /**
     * Get all valid payment types.
     */
    public static function getValidTypes(): array
    {
        return [
            self::TYPE_FULL,
            self::TYPE_DOWN_PAYMENT,
            self::TYPE_FINAL,
        ];
    }
}

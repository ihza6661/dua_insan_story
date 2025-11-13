<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected $fillable = [
        'customer_id',
        'order_number',
        'total_amount',
        'shipping_address',
        'order_status',
    ];

    protected $casts = [
        'total_amount' => 'float',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function invitationDetail(): HasOne
    {
        return $this->hasOne(InvitationDetail::class);
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getRemainingBalanceAttribute()
    {
        $paid = $this->payments()->where('status', 'paid')->sum('amount');
        return $this->total_amount - $paid;
    }

    public function getAmountPaidAttribute()
    {
        return $this->payments()->where('status', 'paid')->sum('amount');
    }
}

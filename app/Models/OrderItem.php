<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'unit_price',
        'sub_total',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function meta()
    {
        return $this->hasMany(OrderItemMeta::class);
    }

    public function designProofs()
    {
        return $this->hasMany(DesignProof::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    /**
     * Check if this order item can be reviewed.
     */
    public function canBeReviewed(): bool
    {
        // Can be reviewed if order is completed or delivered and no review exists
        if ($this->review()->exists()) {
            return false;
        }

        $order = $this->order;
        $reviewableStatuses = ['completed', 'delivered'];

        return in_array($order->order_status, $reviewableStatuses);
    }
}

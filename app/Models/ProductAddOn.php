<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductAddOn extends Pivot
{
    protected $table = 'product_add_ons';

    protected $fillable = [
        'product_id',
        'add_on_id',
        'weight',
    ];

    public $timestamps = false;

    protected $casts = [
        'product_id' => 'integer',
        'add_on_id' => 'integer',
        'weight' => 'integer',
    ];
}

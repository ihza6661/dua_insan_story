<?php

namespace App\Models;

use App\Models\ProductAddOn;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AddOn extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
    ];

    protected $casts = [
        'price' => 'integer',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_add_ons')
            ->using(ProductAddOn::class)
            ->withPivot('weight');
    }

    public function getWeightAttribute(): ?int
    {
        if (array_key_exists('weight', $this->attributes)) {
            return (int) $this->attributes['weight'];
        }

        if ($this->pivot && array_key_exists('weight', $this->pivot->toArray())) {
            return $this->pivot->weight !== null
                ? (int) $this->pivot->weight
                : null;
        }

        return null;
    }
}

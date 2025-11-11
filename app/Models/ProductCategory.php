<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }




    public function getImageAttribute($value): ?string
    {
        return $value ? Storage::disk('public')->url($value) : null;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }


    protected static function booted(): void
    {
        static::deleting(function (ProductCategory $productCategory) {
            if ($productCategory->image) {
                Storage::disk('public')->delete($productCategory->image);
            }
        });
    }
}

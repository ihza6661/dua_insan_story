<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

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




    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        return URL::route('media.stream', ['path' => ltrim($this->image, '/')]);
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

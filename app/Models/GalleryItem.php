<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GalleryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'title',
        'description',
        'category',
        'file_path',
        'media_type',
    ];

    protected $appends = ['file_url'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getFileUrlAttribute(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        $disk = config('filesystems.user_uploads');

        if ($disk === 'cloudinary') {
            // Cloudinary paths are already full URLs
            return $this->file_path;
        }

        // For local storage
        return asset('storage/'.$this->file_path);
    }

    protected static function booted(): void
    {
        static::deleting(function (GalleryItem $galleryItem) {
            if ($galleryItem->file_path) {
                $disk = config('filesystems.user_uploads');
                Storage::disk($disk)->delete($galleryItem->file_path);
            }
        });
    }
}

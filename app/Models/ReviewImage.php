<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Class ReviewImage
 *
 * @property int $id
 * @property int $review_id
 * @property string $image_path
 * @property string $alt_text
 * @property int $display_order
 */
class ReviewImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'image_path',
        'alt_text',
        'display_order',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
        ];
    }

    // ========== RELATIONSHIPS ==========

    /**
     * Get the review that owns this image.
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    // ========== ACCESSORS ==========

    /**
     * Get the full URL for the image.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        // If it's already a full URL, return it
        if (filter_var($this->image_path, FILTER_VALIDATE_URL)) {
            return $this->image_path;
        }

        // Use media streaming route for consistent image delivery
        return URL::route('media.stream', ['path' => ltrim($this->image_path, '/')]);
    }

    /**
     * Get the storage URL for the image.
     */
    public function getStorageUrlAttribute(): string
    {
        if (filter_var($this->image_path, FILTER_VALIDATE_URL)) {
            return $this->image_path;
        }

        return Storage::url($this->image_path);
    }
}

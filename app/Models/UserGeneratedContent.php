<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class UserGeneratedContent extends Model
{
    protected $table = 'user_generated_content';

    protected $fillable = [
        'user_id',
        'order_id',
        'product_id',
        'digital_invitation_id',
        'image_path',
        'caption',
        'instagram_url',
        'instagram_handle',
        'is_approved',
        'is_featured',
        'approved_at',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_featured' => 'boolean',
        'approved_at' => 'datetime',
    ];

    protected $appends = ['image_url'];

    /**
     * Get the full URL for the image
     */
    public function getImageUrlAttribute(): string
    {
        if (!$this->image_path) {
            return '';
        }

        // If already a full URL, return as is
        if (str_starts_with($this->image_path, 'http')) {
            return $this->image_path;
        }

        // Use media stream route for local storage
        return URL::route('media.stream', ['path' => ltrim($this->image_path, '/')]);
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function digitalInvitation(): BelongsTo
    {
        return $this->belongsTo(DigitalInvitation::class);
    }

    /**
     * Scopes
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }
}


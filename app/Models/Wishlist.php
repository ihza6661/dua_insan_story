<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Wishlist extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'product_id',
        'share_token',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($wishlist) {
            if (!$wishlist->share_token) {
                $wishlist->share_token = Str::random(32);
            }
        });
    }

    /**
     * Get the user that owns the wishlist item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product in the wishlist.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Generate a shareable link for this wishlist item.
     */
    public function getShareableLink(): string
    {
        return url("/wishlist/shared/{$this->share_token}");
    }

    /**
     * Scope to get wishlist items by share token.
     */
    public function scopeByShareToken($query, string $token)
    {
        return $query->where('share_token', $token);
    }

    /**
     * Scope to get wishlist items by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}

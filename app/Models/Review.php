<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Review
 *
 * @property int $id
 * @property int $order_item_id
 * @property int $customer_id
 * @property int $product_id
 * @property int $rating
 * @property string $comment
 * @property bool $is_verified
 * @property bool $is_approved
 * @property bool $is_featured
 * @property string $admin_response
 * @property \Carbon\Carbon $admin_responded_at
 * @property int $admin_responder_id
 * @property int $helpful_count
 */
class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'customer_id',
        'product_id',
        'rating',
        'comment',
        'is_verified',
        'is_approved',
        'is_featured',
        'admin_response',
        'admin_responded_at',
        'admin_responder_id',
        'helpful_count',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'is_approved' => 'boolean',
            'is_featured' => 'boolean',
            'admin_responded_at' => 'datetime',
            'helpful_count' => 'integer',
            'rating' => 'integer',
        ];
    }

    // ========== RELATIONSHIPS ==========

    /**
     * Get the order item that this review belongs to.
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Get the customer who wrote this review.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the product being reviewed.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the admin who responded to this review.
     */
    public function adminResponder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_responder_id');
    }

    /**
     * Get all images for this review.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ReviewImage::class)->orderBy('display_order');
    }

    // ========== QUERY SCOPES ==========

    /**
     * Scope a query to only include approved reviews.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope a query to only include pending reviews.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_approved', false);
    }

    /**
     * Scope a query to only include verified reviews.
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope a query to only include featured reviews.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to filter by product.
     */
    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope a query to filter by customer.
     */
    public function scopeByCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope a query to filter by rating.
     */
    public function scopeByRating(Builder $query, int $rating): Builder
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope a query to order by most helpful.
     */
    public function scopeMostHelpful(Builder $query): Builder
    {
        return $query->orderBy('helpful_count', 'desc');
    }

    /**
     * Scope a query to order by most recent.
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope a query to order by highest rating.
     */
    public function scopeHighestRated(Builder $query): Builder
    {
        return $query->orderBy('rating', 'desc');
    }

    /**
     * Scope a query to order by lowest rating.
     */
    public function scopeLowestRated(Builder $query): Builder
    {
        return $query->orderBy('rating', 'asc');
    }

    /**
     * Scope a query to include reviews with images.
     */
    public function scopeWithImages(Builder $query): Builder
    {
        return $query->has('images');
    }
}

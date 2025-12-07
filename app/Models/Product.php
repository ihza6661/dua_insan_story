<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Class Product
 *
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property float $base_price
 * @property int $weight
 * @property int $min_order_quantity
 * @property bool $is_active
 */
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'product_type',
        'template_id',
        'name',
        'slug',
        'description',
        'base_price',
        'weight',
        'min_order_quantity',
        'is_active',
    ];

    /**
     * Boot the model and register observers.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug from name when creating
        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = static::generateUniqueSlug($product->name);
            }
        });

        // Update slug when name changes
        static::updating(function ($product) {
            if ($product->isDirty('name') && empty($product->slug)) {
                $product->slug = static::generateUniqueSlug($product->name);
            }
        });
    }

    /**
     * Generate a unique slug for the product.
     */
    protected static function generateUniqueSlug(string $name, ?int $id = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::slugExists($slug, $id)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if slug exists (excluding current product).
     */
    protected static function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = static::where('slug', $slug);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'weight' => 'integer',
            'base_price' => 'float',
            'min_order_quantity' => 'integer',
        ];
    }

    // ========== RELATIONSHIPS ==========

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * Get the invitation template for digital products.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(InvitationTemplate::class, 'template_id');
    }

    /**
     * Get all images for the product.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * Get all add-ons associated with this product.
     */
    public function addOns(): BelongsToMany
    {
        return $this->belongsToMany(AddOn::class, 'product_add_ons')
            ->using(ProductAddOn::class)
            ->withPivot('weight');
    }

    /**
     * Get all gallery items for this product.
     */
    public function galleryItems(): HasMany
    {
        return $this->hasMany(GalleryItem::class);
    }

    /**
     * Get all cart items containing this product.
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get all order items containing this product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get all variants of this product.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get all reviews for this product.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get approved reviews for this product.
     */
    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    // ========== QUERY SCOPES ==========

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive products.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory(Builder $query, string $categorySlug): Builder
    {
        return $query->whereHas('category', function ($subQuery) use ($categorySlug) {
            $subQuery->where('slug', $categorySlug);
        });
    }

    /**
     * Scope a query to search products by name or description.
     */
    public function scopeSearch(Builder $query, string $searchTerm): Builder
    {
        return $query->where(function ($subQuery) use ($searchTerm) {
            $subQuery->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('description', 'like', "%{$searchTerm}%");
        });
    }

    /**
     * Scope a query to filter products with stock available.
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->whereHas('variants', function ($subQuery) {
            $subQuery->where('stock', '>', 0);
        });
    }

    /**
     * Scope to order products by latest.
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    // ========== REVIEW HELPER METHODS ==========

    /**
     * Get the average rating for this product.
     */
    public function getAverageRatingAttribute(): float
    {
        return round($this->approvedReviews()->avg('rating') ?? 0, 1);
    }

    /**
     * Get the total count of approved reviews.
     */
    public function getReviewCountAttribute(): int
    {
        return $this->approvedReviews()->count();
    }

    /**
     * Get rating breakdown (count per star rating).
     */
    public function getRatingBreakdownAttribute(): array
    {
        $breakdown = [];
        for ($i = 5; $i >= 1; $i--) {
            $breakdown[$i] = $this->approvedReviews()->where('rating', $i)->count();
        }

        return $breakdown;
    }
}

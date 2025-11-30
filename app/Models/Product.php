<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Product
 *
 * @property int $id
 * @property int $category_id
 * @property string $name
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
        'name',
        'description',
        'base_price',
        'weight',
        'min_order_quantity',
        'is_active',
    ];

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
}

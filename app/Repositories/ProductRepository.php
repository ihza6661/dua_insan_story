<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class ProductRepository
 *
 * Repository for handling Product data operations.
 */
class ProductRepository implements ProductRepositoryInterface
{
    protected Product $model;

    /**
     * ProductRepository constructor.
     */
    public function __construct(Product $product)
    {
        $this->model = $product;
    }

    /**
     * {@inheritDoc}
     */
    public function all(array $relations = []): Collection
    {
        return $this->model->with($relations)->latest()->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findById(int $id, array $relations = []): ?Product
    {
        return $this->model->with($relations)->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findByIdOrFail(int $id, array $relations = []): Product
    {
        return $this->model->with($relations)->findOrFail($id);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Product
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->fresh();
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Product $product): bool
    {
        return $product->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function getPaginatedActiveProducts(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->model->with(['category', 'variants.images', 'template'])
            ->active()
            ->when($filters['search'] ?? null, function ($query, $searchTerm) {
                $query->search($searchTerm);
            })
            ->when($filters['category_slug'] ?? null, function ($query, $categorySlug) {
                $query->byCategory($categorySlug);
            })
            ->when($filters['min_price'] ?? null, function ($query, $minPrice) {
                $query->whereHas('variants', function ($subQuery) use ($minPrice) {
                    $subQuery->where('price', '>=', $minPrice);
                });
            })
            ->when($filters['max_price'] ?? null, function ($query, $maxPrice) {
                $query->whereHas('variants', function ($subQuery) use ($maxPrice) {
                    $subQuery->where('price', '<=', $maxPrice);
                });
            });

        // Apply sorting
        $sort = $filters['sort'] ?? 'latest';
        switch ($sort) {
            case 'price_asc':
            case 'price_desc':
                $query->addSelect([
                    'min_price' => ProductVariant::selectRaw('MIN(price)')
                        ->whereColumn('product_id', 'products.id')
                        ->take(1),
                ])->orderBy('min_price', $sort === 'price_asc' ? 'asc' : 'desc');
                break;
            case 'latest':
            default:
                $query->latest();
                break;
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findActiveProduct(int $id, array $relations = []): Product
    {
        return $this->model->with($relations)
            ->active()
            ->findOrFail($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findActiveProductBySlug(string $slug, array $relations = []): Product
    {
        return $this->model->with($relations)
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * {@inheritDoc}
     */
    public function hasDependencies(Product $product): bool
    {
        return $product->orderItems()->exists() || $product->cartItems()->exists();
    }
}

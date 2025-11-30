<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface ProductRepositoryInterface
 *
 * Defines the contract for Product repository operations.
 */
interface ProductRepositoryInterface
{
    /**
     * Get all products with optional relationships.
     */
    public function all(array $relations = []): Collection;

    /**
     * Find a product by ID with optional relationships.
     */
    public function findById(int $id, array $relations = []): ?Product;

    /**
     * Find a product by ID or fail.
     */
    public function findByIdOrFail(int $id, array $relations = []): Product;

    /**
     * Create a new product.
     */
    public function create(array $data): Product;

    /**
     * Update an existing product.
     */
    public function update(Product $product, array $data): Product;

    /**
     * Delete a product.
     */
    public function delete(Product $product): bool;

    /**
     * Get paginated active products with filters.
     */
    public function getPaginatedActiveProducts(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Find active product by ID.
     */
    public function findActiveProduct(int $id, array $relations = []): Product;

    /**
     * Check if product has dependencies (orders or cart items).
     */
    public function hasDependencies(Product $product): bool;
}

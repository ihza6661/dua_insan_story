<?php

namespace App\Repositories\Contracts;

use App\Models\Review;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface ReviewRepositoryInterface
 *
 * Defines the contract for Review repository operations.
 */
interface ReviewRepositoryInterface
{
    /**
     * Get all reviews with optional relationships.
     */
    public function all(array $relations = []): Collection;

    /**
     * Find a review by ID with optional relationships.
     */
    public function findById(int $id, array $relations = []): ?Review;

    /**
     * Find a review by ID or fail.
     */
    public function findByIdOrFail(int $id, array $relations = []): Review;

    /**
     * Create a new review.
     */
    public function create(array $data): Review;

    /**
     * Update an existing review.
     */
    public function update(Review $review, array $data): Review;

    /**
     * Delete a review.
     */
    public function delete(Review $review): bool;

    /**
     * Get paginated reviews for a product.
     */
    public function getPaginatedProductReviews(int $productId, array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Get paginated reviews for admin with filters.
     */
    public function getPaginatedReviewsForAdmin(array $filters = [], int $perPage = 10): LengthAwarePaginator;

    /**
     * Check if customer can review an order item.
     */
    public function canCustomerReviewOrderItem(int $customerId, int $orderItemId): bool;

    /**
     * Get review by order item and customer.
     */
    public function findByOrderItemAndCustomer(int $orderItemId, int $customerId): ?Review;

    /**
     * Get product rating summary.
     */
    public function getProductRatingSummary(int $productId): array;
}

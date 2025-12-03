<?php

namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class ReviewRepository
 *
 * Repository for handling Review data operations.
 */
class ReviewRepository implements ReviewRepositoryInterface
{
    protected Review $model;

    /**
     * ReviewRepository constructor.
     */
    public function __construct(Review $review)
    {
        $this->model = $review;
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
    public function findById(int $id, array $relations = []): ?Review
    {
        return $this->model->with($relations)->find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findByIdOrFail(int $id, array $relations = []): Review
    {
        return $this->model->with($relations)->findOrFail($id);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Review
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(Review $review, array $data): Review
    {
        $review->update($data);

        return $review->fresh();
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Review $review): bool
    {
        return $review->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function getPaginatedProductReviews(int $productId, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->with(['customer', 'images'])
            ->forProduct($productId)
            ->approved();

        // Apply filters
        if (isset($filters['rating']) && $filters['rating'] > 0) {
            $query->byRating($filters['rating']);
        }

        if (isset($filters['with_images']) && $filters['with_images']) {
            $query->withImages();
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'latest';
        switch ($sortBy) {
            case 'helpful':
                $query->mostHelpful();
                break;
            case 'highest':
                $query->highestRated();
                break;
            case 'lowest':
                $query->lowestRated();
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
    public function getPaginatedReviewsForAdmin(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->model->query()
            ->with(['customer', 'product', 'images']);

        // Apply filters
        if (isset($filters['is_approved'])) {
            if ($filters['is_approved'] === 'pending') {
                $query->pending();
            } elseif ($filters['is_approved'] === 'approved') {
                $query->approved();
            }
        }

        if (isset($filters['rating']) && $filters['rating'] > 0) {
            $query->byRating($filters['rating']);
        }

        if (isset($filters['product_id']) && $filters['product_id'] > 0) {
            $query->forProduct($filters['product_id']);
        }

        if (isset($filters['is_featured'])) {
            $query->featured();
        }

        if (isset($filters['search']) && ! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('full_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('product', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function canCustomerReviewOrderItem(int $customerId, int $orderItemId): bool
    {
        // Check if order item exists and belongs to customer
        $orderItem = OrderItem::with('order')->find($orderItemId);

        if (! $orderItem || $orderItem->order->customer_id !== $customerId) {
            return false;
        }

        // Check if order is in reviewable status (completed or delivered)
        $reviewableStatuses = [Order::STATUS_COMPLETED, Order::STATUS_DELIVERED];
        if (! in_array($orderItem->order->order_status, $reviewableStatuses)) {
            return false;
        }

        // Check if review already exists
        $existingReview = $this->findByOrderItemAndCustomer($orderItemId, $customerId);

        return $existingReview === null;
    }

    /**
     * {@inheritDoc}
     */
    public function findByOrderItemAndCustomer(int $orderItemId, int $customerId): ?Review
    {
        return $this->model->where('order_item_id', $orderItemId)
            ->where('customer_id', $customerId)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getProductRatingSummary(int $productId): array
    {
        $reviews = $this->model->forProduct($productId)->approved()->get();

        $totalReviews = $reviews->count();
        $averageRating = $totalReviews > 0 ? round($reviews->avg('rating'), 1) : 0;

        // Rating breakdown
        $breakdown = [];
        for ($i = 5; $i >= 1; $i--) {
            $count = $reviews->where('rating', $i)->count();
            $percentage = $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0;
            $breakdown[$i] = [
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        return [
            'total_reviews' => $totalReviews,
            'average_rating' => $averageRating,
            'rating_breakdown' => $breakdown,
        ];
    }
}

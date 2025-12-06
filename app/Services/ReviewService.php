<?php

namespace App\Services;

use App\Helpers\SecurityHelper;
use App\Models\Review;
use App\Models\ReviewImage;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Class ReviewService
 *
 * Business logic layer for Review operations.
 */
class ReviewService
{
    /**
     * ReviewService constructor.
     */
    public function __construct(
        protected ReviewRepositoryInterface $reviewRepository
    ) {}

    /**
     * Create a new review from a customer.
     *
     * @throws Exception
     */
    public function createReview(int $customerId, array $validatedData): Review
    {
        // Check if customer can review this order item
        if (! $this->reviewRepository->canCustomerReviewOrderItem($customerId, $validatedData['order_item_id'])) {
            throw new Exception('Anda tidak dapat memberikan ulasan untuk item pesanan ini.');
        }

        // Prevent review spam - one review per product per 24 hours
        $recentReview = Review::where('customer_id', $customerId)
            ->where('product_id', $validatedData['product_id'])
            ->where('created_at', '>', now()->subHours(24))
            ->exists();
            
        if ($recentReview) {
            throw new Exception('Anda hanya dapat memberikan ulasan untuk produk ini sekali per 24 jam.');
        }

        DB::beginTransaction();
        try {
            // Create the review with sanitized comment
            $reviewData = [
                'order_item_id' => $validatedData['order_item_id'],
                'customer_id' => $customerId,
                'product_id' => $validatedData['product_id'],
                'rating' => $validatedData['rating'],
                'comment' => isset($validatedData['comment']) 
                    ? SecurityHelper::sanitizeText($validatedData['comment'])
                    : null,
                'is_verified' => true, // Always verified for order-based reviews
                'is_approved' => true, // Auto-approve by default (can be changed to false for moderation)
            ];

            $review = $this->reviewRepository->create($reviewData);

            // Handle image uploads if provided
            if (isset($validatedData['images']) && is_array($validatedData['images'])) {
                $this->uploadReviewImages($review, $validatedData['images']);
            }

            DB::commit();

            return $review->load(['images', 'product', 'customer']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing review.
     *
     * @throws Exception
     */
    public function updateReview(Review $review, int $customerId, array $validatedData): Review
    {
        // Verify ownership
        if ($review->customer_id !== $customerId) {
            throw new Exception('Anda tidak memiliki akses untuk mengubah ulasan ini.');
        }

        DB::beginTransaction();
        try {
            $updateData = [
                'rating' => $validatedData['rating'],
                'comment' => isset($validatedData['comment'])
                    ? SecurityHelper::sanitizeText($validatedData['comment'])
                    : null,
            ];

            $review = $this->reviewRepository->update($review, $updateData);

            // Handle new image uploads if provided
            if (isset($validatedData['images']) && is_array($validatedData['images'])) {
                $this->uploadReviewImages($review, $validatedData['images']);
            }

            DB::commit();

            return $review->load(['images', 'product', 'customer']);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a review.
     *
     * @throws Exception
     */
    public function deleteReview(Review $review, int $customerId): void
    {
        // Verify ownership
        if ($review->customer_id !== $customerId) {
            throw new Exception('Anda tidak memiliki akses untuk menghapus ulasan ini.');
        }

        DB::beginTransaction();
        try {
            // Delete all review images
            foreach ($review->images as $image) {
                $this->deleteReviewImage($image);
            }

            $this->reviewRepository->delete($review);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get paginated reviews for a product.
     */
    public function getProductReviews(int $productId, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->reviewRepository->getPaginatedProductReviews($productId, $filters, $perPage);
    }

    /**
     * Get product rating summary.
     */
    public function getProductRatingSummary(int $productId): array
    {
        return $this->reviewRepository->getProductRatingSummary($productId);
    }

    /**
     * Admin: Get paginated reviews with filters.
     */
    public function getAllReviewsForAdmin(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        return $this->reviewRepository->getPaginatedReviewsForAdmin($filters, $perPage);
    }

    /**
     * Admin: Approve a review.
     */
    public function approveReview(Review $review): Review
    {
        return $this->reviewRepository->update($review, ['is_approved' => true]);
    }

    /**
     * Admin: Reject a review.
     */
    public function rejectReview(Review $review): Review
    {
        return $this->reviewRepository->update($review, ['is_approved' => false]);
    }

    /**
     * Admin: Toggle featured status.
     */
    public function toggleFeatured(Review $review): Review
    {
        return $this->reviewRepository->update($review, ['is_featured' => ! $review->is_featured]);
    }

    /**
     * Admin: Add response to a review.
     */
    public function addAdminResponse(Review $review, int $adminId, string $response): Review
    {
        $data = [
            'admin_response' => SecurityHelper::sanitizeText($response),
            'admin_responder_id' => $adminId,
            'admin_responded_at' => now(),
        ];

        return $this->reviewRepository->update($review, $data);
    }

    /**
     * Admin: Delete a review.
     */
    public function deleteReviewByAdmin(Review $review): void
    {
        DB::beginTransaction();
        try {
            // Delete all review images
            foreach ($review->images as $image) {
                $this->deleteReviewImage($image);
            }

            $this->reviewRepository->delete($review);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Upload multiple images for a review.
     */
    protected function uploadReviewImages(Review $review, array $images): void
    {
        $directory = 'review-images';
        $disk = Storage::disk('public');

        if (! $disk->exists($directory)) {
            $disk->makeDirectory($directory, 0777, true);
        }

        $displayOrder = $review->images()->count();

        foreach ($images as $imageFile) {
            if ($imageFile instanceof UploadedFile) {
                $path = $imageFile->store($directory, 'public');

                ReviewImage::create([
                    'review_id' => $review->id,
                    'image_path' => $path,
                    'display_order' => $displayOrder++,
                ]);
            }
        }
    }

    /**
     * Delete a review image.
     */
    public function deleteReviewImage(ReviewImage $image): void
    {
        // Delete from storage
        if ($image->image_path && Storage::disk('public')->exists($image->image_path)) {
            Storage::disk('public')->delete($image->image_path);
        }

        // Delete from database
        $image->delete();
    }

    /**
     * Increment helpful count for a review.
     */
    public function markAsHelpful(Review $review): Review
    {
        return $this->reviewRepository->update($review, [
            'helpful_count' => $review->helpful_count + 1,
        ]);
    }

    /**
     * Get review by ID.
     */
    public function getReviewById(int $id, array $relations = []): ?Review
    {
        return $this->reviewRepository->findById($id, $relations);
    }

    /**
     * Get review by ID or fail.
     */
    public function getReviewByIdOrFail(int $id, array $relations = []): Review
    {
        return $this->reviewRepository->findByIdOrFail($id, $relations);
    }
}

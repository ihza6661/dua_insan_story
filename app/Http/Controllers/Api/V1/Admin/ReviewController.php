<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Review\AdminResponseRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Models\ReviewImage;
use App\Services\CacheService;
use App\Services\ReviewService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin Review Controller
 *
 * Handles admin review management operations.
 */
class ReviewController extends Controller
{
    public function __construct(
        protected ReviewService $reviewService
    ) {}

    /**
     * Get all reviews with filters for admin.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'is_approved' => $request->query('is_approved'), // 'pending', 'approved', or null for all
                'rating' => $request->query('rating'),
                'product_id' => $request->query('product_id'),
                'is_featured' => $request->query('is_featured'),
                'search' => $request->query('search'),
            ];

            $perPage = $request->query('per_page', 10);

            $reviews = $this->reviewService->getAllReviewsForAdmin($filters, $perPage);

            return response()->json([
                'message' => 'Daftar ulasan berhasil diambil.',
                'data' => ReviewResource::collection($reviews),
                'meta' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil ulasan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified review.
     */
    public function show(Review $review): JsonResponse
    {
        $review->load(['customer', 'product', 'images', 'adminResponder', 'orderItem.order']);

        return response()->json([
            'message' => 'Ulasan berhasil diambil.',
            'data' => new ReviewResource($review),
        ]);
    }

    /**
     * Approve a review.
     */
    public function approve(Review $review): JsonResponse
    {
        try {
            $updatedReview = $this->reviewService->approveReview($review);
            CacheService::invalidateReviews();

            return response()->json([
                'message' => 'Ulasan berhasil disetujui.',
                'data' => new ReviewResource($updatedReview),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat menyetujui ulasan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a review.
     */
    public function reject(Review $review): JsonResponse
    {
        try {
            $updatedReview = $this->reviewService->rejectReview($review);
            CacheService::invalidateReviews();

            return response()->json([
                'message' => 'Ulasan berhasil ditolak.',
                'data' => new ReviewResource($updatedReview),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat menolak ulasan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle featured status.
     */
    public function toggleFeatured(Review $review): JsonResponse
    {
        try {
            $updatedReview = $this->reviewService->toggleFeatured($review);
            CacheService::invalidateReviews();

            $message = $updatedReview->is_featured
                ? 'Ulasan berhasil ditandai sebagai unggulan.'
                : 'Ulasan berhasil dihapus dari unggulan.';

            return response()->json([
                'message' => $message,
                'data' => new ReviewResource($updatedReview),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengubah status unggulan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add admin response to a review.
     */
    public function addResponse(AdminResponseRequest $request, Review $review): JsonResponse
    {
        try {
            $adminId = $request->user()->id;
            $response = $request->validated()['admin_response'];

            $updatedReview = $this->reviewService->addAdminResponse($review, $adminId, $response);

            return response()->json([
                'message' => 'Respon admin berhasil ditambahkan.',
                'data' => new ReviewResource($updatedReview),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat menambahkan respon.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a review.
     */
    public function destroy(Review $review): JsonResponse
    {
        try {
            $this->reviewService->deleteReviewByAdmin($review);
            CacheService::invalidateReviews();

            return response()->json([
                'message' => 'Ulasan berhasil dihapus.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus ulasan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a review image.
     */
    public function deleteImage(ReviewImage $reviewImage): JsonResponse
    {
        try {
            $this->reviewService->deleteReviewImage($reviewImage);

            return response()->json([
                'message' => 'Gambar ulasan berhasil dihapus.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus gambar.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get review statistics.
     * Optimized to use single query with aggregations (9 queries → 1)
     * Cached for 5 minutes
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = CacheService::remember(CacheService::TAG_REVIEWS, 'review_statistics', 300, function () {
                // Single optimized query with all aggregations
                $result = DB::table('reviews')
                    ->select([
                        DB::raw('COUNT(*) as total_reviews'),
                        DB::raw('SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as pending_reviews'),
                        DB::raw('SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved_reviews'),
                        DB::raw('SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as featured_reviews'),
                        DB::raw('AVG(CASE WHEN is_approved = 1 THEN rating ELSE NULL END) as average_rating'),
                        DB::raw('SUM(CASE WHEN EXISTS (SELECT 1 FROM review_images WHERE review_images.review_id = reviews.id) THEN 1 ELSE 0 END) as reviews_with_images'),
                        DB::raw('SUM(CASE WHEN is_approved = 1 AND rating = 5 THEN 1 ELSE 0 END) as rating_5'),
                        DB::raw('SUM(CASE WHEN is_approved = 1 AND rating = 4 THEN 1 ELSE 0 END) as rating_4'),
                        DB::raw('SUM(CASE WHEN is_approved = 1 AND rating = 3 THEN 1 ELSE 0 END) as rating_3'),
                        DB::raw('SUM(CASE WHEN is_approved = 1 AND rating = 2 THEN 1 ELSE 0 END) as rating_2'),
                        DB::raw('SUM(CASE WHEN is_approved = 1 AND rating = 1 THEN 1 ELSE 0 END) as rating_1'),
                    ])
                    ->first();

                return [
                    'total_reviews' => (int) $result->total_reviews,
                    'pending_reviews' => (int) $result->pending_reviews,
                    'approved_reviews' => (int) $result->approved_reviews,
                    'featured_reviews' => (int) $result->featured_reviews,
                    'average_rating' => round((float) ($result->average_rating ?? 0), 1),
                    'reviews_with_images' => (int) $result->reviews_with_images,
                    'rating_distribution' => [
                        '5_star' => (int) $result->rating_5,
                        '4_star' => (int) $result->rating_4,
                        '3_star' => (int) $result->rating_3,
                        '2_star' => (int) $result->rating_2,
                        '1_star' => (int) $result->rating_1,
                    ],
                ];
            });

            return response()->json([
                'message' => 'Statistik ulasan berhasil diambil.',
                'data' => $stats,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil statistik.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Review\AdminResponseRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Models\ReviewImage;
use App\Services\ReviewService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_reviews' => Review::count(),
                'pending_reviews' => Review::pending()->count(),
                'approved_reviews' => Review::approved()->count(),
                'featured_reviews' => Review::featured()->count(),
                'average_rating' => round(Review::approved()->avg('rating') ?? 0, 1),
                'reviews_with_images' => Review::withImages()->count(),
                'rating_distribution' => [
                    '5_star' => Review::approved()->where('rating', 5)->count(),
                    '4_star' => Review::approved()->where('rating', 4)->count(),
                    '3_star' => Review::approved()->where('rating', 3)->count(),
                    '2_star' => Review::approved()->where('rating', 2)->count(),
                    '1_star' => Review::approved()->where('rating', 1)->count(),
                ],
            ];

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

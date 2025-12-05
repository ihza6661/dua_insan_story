<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\Review\StoreRequest;
use App\Http\Requests\Api\V1\Customer\Review\UpdateRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Services\ReviewService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer Review Controller
 *
 * Handles customer review operations (create, update, delete).
 */
class ReviewController extends Controller
{
    public function __construct(
        protected ReviewService $reviewService
    ) {}

    /**
     * Get reviews for a specific product.
     */
    public function index(Request $request, int $productId): JsonResponse
    {
        try {
            $filters = [
                'rating' => $request->query('rating'),
                'with_images' => $request->query('with_images', false),
                'sort_by' => $request->query('sort_by', 'latest'), // latest, helpful, highest, lowest
            ];

            $perPage = $request->query('per_page', 10);

            $reviews = $this->reviewService->getProductReviews($productId, $filters, $perPage);

            return response()->json([
                'message' => 'Ulasan produk berhasil diambil.',
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
     * Get rating summary for a product.
     */
    public function getRatingSummary(int $productId): JsonResponse
    {
        try {
            $summary = $this->reviewService->getProductRatingSummary($productId);

            return response()->json([
                'message' => 'Ringkasan rating berhasil diambil.',
                'data' => $summary,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil ringkasan rating.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new review.
     */
    public function store(StoreRequest $request): JsonResponse
    {
        try {
            $customerId = $request->user()->id;
            $review = $this->reviewService->createReview($customerId, $request->validated());

            return response()->json([
                'message' => 'Ulasan berhasil dibuat.',
                'data' => new ReviewResource($review),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified review.
     */
    /**
     * Display the specified review.
     */
    public function show(Review $review): JsonResponse
    {
        $review->load(['customer', 'product', 'images', 'adminResponder']);

        return response()->json([
            'message' => 'Ulasan berhasil diambil.',
            'data' => new ReviewResource($review),
        ]);
    }

    /**
     * Update the specified review.
     */
    public function update(UpdateRequest $request, Review $review): JsonResponse
    {
        try {
            $customerId = $request->user()->id;
            $updatedReview = $this->reviewService->updateReview($review, $customerId, $request->validated());

            return response()->json([
                'message' => 'Ulasan berhasil diperbarui.',
                'data' => new ReviewResource($updatedReview),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified review.
     */
    public function destroy(Request $request, Review $review): JsonResponse
    {
        try {
            $customerId = $request->user()->id;
            $this->reviewService->deleteReview($review, $customerId);

            return response()->json([
                'message' => 'Ulasan berhasil dihapus.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mark a review as helpful.
     */
    public function markAsHelpful(Review $review): JsonResponse
    {
        try {
            $updatedReview = $this->reviewService->markAsHelpful($review);

            return response()->json([
                'message' => 'Ulasan ditandai sebagai membantu.',
                'data' => new ReviewResource($updatedReview),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get customer's own reviews.
     */
    public function myReviews(Request $request): JsonResponse
    {
        try {
            $customerId = $request->user()->id;
            $reviews = Review::with(['product', 'images'])
                ->where('customer_id', $customerId)
                ->latest()
                ->paginate(10);

            return response()->json([
                'message' => 'Ulasan Anda berhasil diambil.',
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
                'message' => 'Terjadi kesalahan saat mengambil ulasan Anda.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get products that the customer can review from completed orders.
     */
    public function getReviewableProducts(Request $request): JsonResponse
    {
        try {
            $customerId = $request->user()->id;

            $reviewableItems = \App\Models\OrderItem::whereHas('order', function ($query) use ($customerId) {
                $query->where('customer_id', $customerId)
                    ->whereIn('order_status', ['Completed', 'Delivered']);
            })
                ->whereDoesntHave('review')
                ->with(['product.variants.images', 'order'])
                ->latest()
                ->get();

            return response()->json([
                'message' => 'Produk yang dapat diulas berhasil diambil.',
                'data' => $reviewableItems->map(function ($item) {
                    // Get featured image from first variant
                    $defaultVariant = $item->product->variants->first();
                    $featuredImage = null;
                    if ($defaultVariant && $defaultVariant->images->isNotEmpty()) {
                        $featuredImage = $defaultVariant->images->firstWhere('is_featured', true)
                                      ?? $defaultVariant->images->first();
                    }

                    return [
                        'order_item_id' => $item->id,
                        'order_number' => $item->order->order_number,
                        'product' => [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'image' => $featuredImage?->image_url,
                        ],
                        'purchased_at' => $item->order->created_at,
                    ];
                }),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil produk yang dapat diulas.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

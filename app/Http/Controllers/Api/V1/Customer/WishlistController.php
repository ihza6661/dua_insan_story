<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    /**
     * Get authenticated user's wishlist
     */
    public function index(): JsonResponse
    {
        $wishlists = Wishlist::with(['product.category'])
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Wishlist retrieved successfully',
            'data' => $wishlists->map(function ($wishlist) {
                return [
                    'id' => $wishlist->id,
                    'product' => [
                        'id' => $wishlist->product->id,
                        'name' => $wishlist->product->name,
                        'base_price' => $wishlist->product->base_price,
                        'slug' => $wishlist->product->slug,
                        'category' => $wishlist->product->category,
                        'featured_image' => $wishlist->product->featured_image ?? null,
                        'is_active' => $wishlist->product->is_active,
                    ],
                    'share_token' => $wishlist->share_token,
                    'shareable_link' => $wishlist->getShareableLink(),
                    'added_at' => $wishlist->created_at,
                ];
            }),
        ]);
    }

    /**
     * Add product to wishlist
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $userId = Auth::id();
        $productId = $request->product_id;

        // Check if already in wishlist
        $existing = Wishlist::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Product already in wishlist',
                'data' => $existing,
            ], 200);
        }

        $wishlist = Wishlist::create([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);

        $wishlist->load(['product.category']);

        return response()->json([
            'message' => 'Product added to wishlist',
            'data' => [
                'id' => $wishlist->id,
                'product' => [
                    'id' => $wishlist->product->id,
                    'name' => $wishlist->product->name,
                    'base_price' => $wishlist->product->base_price,
                    'slug' => $wishlist->product->slug,
                    'category' => $wishlist->product->category,
                    'featured_image' => $wishlist->product->featuredImage,
                ],
                'share_token' => $wishlist->share_token,
                'shareable_link' => $wishlist->getShareableLink(),
                'added_at' => $wishlist->created_at,
            ],
        ], 201);
    }

    /**
     * Remove product from wishlist
     */
    public function destroy(int $id): JsonResponse
    {
        $wishlist = Wishlist::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if (!$wishlist) {
            return response()->json([
                'message' => 'Wishlist item not found',
            ], 404);
        }

        $wishlist->delete();

        return response()->json([
            'message' => 'Product removed from wishlist',
        ]);
    }

    /**
     * Get shareable wishlist by token (public access)
     */
    public function getByShareToken(string $token): JsonResponse
    {
        $wishlists = Wishlist::with(['product.category', 'user'])
            ->where('share_token', $token)
            ->orWhereHas('user', function ($query) use ($token) {
                // Get all wishlist items for the user who owns this share token
                $wishlist = Wishlist::where('share_token', $token)->first();
                if ($wishlist) {
                    $query->where('id', $wishlist->user_id);
                }
            })
            ->latest()
            ->get();

        if ($wishlists->isEmpty()) {
            return response()->json([
                'message' => 'Wishlist not found',
            ], 404);
        }

        $owner = $wishlists->first()->user;

        return response()->json([
            'message' => 'Wishlist retrieved successfully',
            'data' => [
                'owner' => [
                    'name' => $owner->full_name,
                ],
                'items' => $wishlists->map(function ($wishlist) {
                    return [
                        'id' => $wishlist->id,
                        'product' => [
                            'id' => $wishlist->product->id,
                            'name' => $wishlist->product->name,
                            'base_price' => $wishlist->product->base_price,
                            'slug' => $wishlist->product->slug,
                            'category' => $wishlist->product->category,
                            'featured_image' => $wishlist->product->featured_image ?? null,
                            'is_active' => $wishlist->product->is_active,
                        ],
                        'added_at' => $wishlist->created_at,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Check if product is in user's wishlist
     */
    public function check(int $productId): JsonResponse
    {
        $inWishlist = Wishlist::where('user_id', Auth::id())
            ->where('product_id', $productId)
            ->exists();

        return response()->json([
            'in_wishlist' => $inWishlist,
        ]);
    }
}

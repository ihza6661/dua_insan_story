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
        $wishlists = Wishlist::with(['product.category', 'product.images'])
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        // Get the share token and link from the first wishlist item (all belong to same user)
        $firstWishlist = $wishlists->first();

        return response()->json([
            'message' => 'Wishlist retrieved successfully',
            'data' => [
                'items' => $wishlists->map(function ($wishlist) {
                    // Get featured image (first featured image or first image)
                    $featuredImage = $wishlist->product->images->firstWhere('is_featured', true) 
                        ?? $wishlist->product->images->first();
                    
                    return [
                        'id' => $wishlist->id,
                        'product' => [
                            'id' => $wishlist->product->id,
                            'name' => $wishlist->product->name,
                            'base_price' => $wishlist->product->base_price,
                            'slug' => $wishlist->product->slug,
                            'category' => $wishlist->product->category,
                            'featured_image' => $featuredImage ? [
                                'id' => $featuredImage->id,
                                'image' => $featuredImage->image,
                                'image_url' => $featuredImage->image_url,
                                'alt_text' => $featuredImage->alt_text,
                                'is_featured' => $featuredImage->is_featured,
                            ] : null,
                            'is_active' => $wishlist->product->is_active,
                        ],
                        'share_token' => $wishlist->share_token,
                        'shareable_link' => $wishlist->getShareableLink(),
                        'added_at' => $wishlist->created_at,
                    ];
                }),
                'share_token' => $firstWishlist?->share_token,
                'shareable_link' => $firstWishlist?->getShareableLink(),
            ],
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

        $wishlist->load(['product.category', 'product.images']);

        // Get featured image (first featured image or first image)
        $featuredImage = $wishlist->product->images->firstWhere('is_featured', true) 
            ?? $wishlist->product->images->first();

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
                    'featured_image' => $featuredImage ? [
                        'id' => $featuredImage->id,
                        'image' => $featuredImage->image,
                        'image_url' => $featuredImage->image_url,
                        'alt_text' => $featuredImage->alt_text,
                        'is_featured' => $featuredImage->is_featured,
                    ] : null,
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
        $wishlists = Wishlist::with(['product.category', 'product.images', 'user'])
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
                    // Get featured image (first featured image or first image)
                    $featuredImage = $wishlist->product->images->firstWhere('is_featured', true) 
                        ?? $wishlist->product->images->first();
                    
                    return [
                        'id' => $wishlist->id,
                        'product' => [
                            'id' => $wishlist->product->id,
                            'name' => $wishlist->product->name,
                            'base_price' => $wishlist->product->base_price,
                            'slug' => $wishlist->product->slug,
                            'category' => $wishlist->product->category,
                            'featured_image' => $featuredImage ? [
                                'id' => $featuredImage->id,
                                'image' => $featuredImage->image,
                                'image_url' => $featuredImage->image_url,
                                'alt_text' => $featuredImage->alt_text,
                                'is_featured' => $featuredImage->is_featured,
                            ] : null,
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
        $wishlist = Wishlist::where('user_id', Auth::id())
            ->where('product_id', $productId)
            ->first();

        return response()->json([
            'message' => 'Wishlist status retrieved',
            'data' => [
                'in_wishlist' => (bool) $wishlist,
                'wishlist_id' => $wishlist?->id,
            ],
        ]);
    }
}

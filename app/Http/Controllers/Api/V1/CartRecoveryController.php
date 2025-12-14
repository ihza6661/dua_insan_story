<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\AbandonedCart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartRecoveryController extends Controller
{
    /**
     * Get abandoned cart details by recovery token
     */
    public function show(string $token): JsonResponse
    {
        $abandonedCart = AbandonedCart::where('recovery_token', $token)->first();

        if (!$abandonedCart) {
            return response()->json([
                'message' => 'Token pemulihan keranjang tidak valid.',
                'error' => 'invalid_token',
            ], 404);
        }

        if ($abandonedCart->is_recovered) {
            return response()->json([
                'message' => 'Keranjang ini sudah berhasil dipulihkan sebelumnya.',
                'error' => 'already_recovered',
                'recovered_at' => $abandonedCart->recovered_at,
            ], 400);
        }

        // Check if token is too old (e.g., older than 30 days)
        if ($abandonedCart->abandoned_at->diffInDays(now()) > 30) {
            return response()->json([
                'message' => 'Token pemulihan sudah kedaluwarsa. Silakan mulai belanja lagi.',
                'error' => 'token_expired',
            ], 400);
        }

        return response()->json([
            'message' => 'Data keranjang ditemukan.',
            'data' => [
                'email' => $abandonedCart->email,
                'name' => $abandonedCart->name,
                'items' => $abandonedCart->cart_items,
                'total' => $abandonedCart->cart_total,
                'items_count' => $abandonedCart->items_count,
                'abandoned_at' => $abandonedCart->abandoned_at,
            ],
        ]);
    }

    /**
     * Recover abandoned cart and restore items to user's active cart
     */
    public function recover(string $token, Request $request, CartService $cartService): JsonResponse
    {
        $abandonedCart = AbandonedCart::where('recovery_token', $token)->first();

        if (!$abandonedCart) {
            return response()->json([
                'message' => 'Token pemulihan keranjang tidak valid.',
                'error' => 'invalid_token',
            ], 404);
        }

        if ($abandonedCart->is_recovered) {
            return response()->json([
                'message' => 'Keranjang ini sudah berhasil dipulihkan sebelumnya.',
                'error' => 'already_recovered',
            ], 400);
        }

        // Check if token is too old (e.g., older than 30 days)
        if ($abandonedCart->abandoned_at->diffInDays(now()) > 30) {
            return response()->json([
                'message' => 'Token pemulihan sudah kedaluwarsa. Silakan mulai belanja lagi.',
                'error' => 'token_expired',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Get or create active cart for the user/session
            $activeCart = $cartService->getCartContents($request);

            $recoveredCount = 0;
            $unavailableItems = [];

            // Restore cart items from abandoned cart
            foreach ($abandonedCart->cart_items as $item) {
                // Check if variant still exists and is available
                $variant = ProductVariant::find($item['variant_id'] ?? null);

                if (!$variant || !$variant->is_active) {
                    $unavailableItems[] = [
                        'product_name' => $item['product_name'] ?? 'Produk tidak tersedia',
                        'variant_name' => $item['variant_name'] ?? '',
                    ];
                    continue;
                }

                // Check if item already exists in active cart
                $existingItem = CartItem::where('cart_id', $activeCart->id)
                    ->where('product_variant_id', $variant->id)
                    ->first();

                if ($existingItem) {
                    // Update quantity if item exists
                    $existingItem->quantity += $item['quantity'];
                    $existingItem->save();
                } else {
                    // Create new cart item
                    CartItem::create([
                        'cart_id' => $activeCart->id,
                        'product_id' => $item['product_id'],
                        'product_variant_id' => $variant->id,
                        'quantity' => $item['quantity'],
                    ]);
                }

                $recoveredCount++;
            }

            // Mark abandoned cart as recovered (without order_id yet, since order hasn't been placed)
            // We'll update with order_id later when checkout completes
            $abandonedCart->update([
                'is_recovered' => true,
                'recovered_at' => now(),
                'recovery_source' => 'email_link',
            ]);

            DB::commit();

            // Reload cart with relationships
            $activeCart->load('items.variant.images', 'items.product.addOns');

            Log::info('Cart recovered successfully', [
                'abandoned_cart_id' => $abandonedCart->id,
                'user_id' => Auth::id(),
                'recovered_items' => $recoveredCount,
                'unavailable_items' => count($unavailableItems),
            ]);

            $response = [
                'message' => 'Keranjang berhasil dipulihkan!',
                'data' => [
                    'cart' => new CartResource($activeCart),
                    'recovered_items_count' => $recoveredCount,
                    'unavailable_items' => $unavailableItems,
                ],
            ];

            if (count($unavailableItems) > 0) {
                $response['warning'] = 'Beberapa produk tidak lagi tersedia dan tidak ditambahkan ke keranjang.';
            }

            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to recover cart', [
                'abandoned_cart_id' => $abandonedCart->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Gagal memulihkan keranjang. Silakan coba lagi.',
                'error' => 'recovery_failed',
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function show(Request $request, CartService $cartService): JsonResponse
    {
    $cart = $cartService->getCartContents($request);
$cart->load('items.variant.images', 'items.product.addOns');
        $response = (new CartResource($cart))->response();

        if (!Auth::check() && $cart->session_id) {
            $response->header('X-Session-ID', $cart->session_id);
        }

        return $response;
    }

    public function clear(Request $request, CartService $cartService): JsonResponse
    {
        $cart = $cartService->clearCart($request);

        return response()->json([
            'message' => 'Keranjang belanja berhasil dikosongkan.',
            'data' => new CartResource($cart),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cart\StoreItemRequest;
use App\Http\Requests\Api\V1\Cart\UpdateItemRequest;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartItemController extends Controller
{
    public function store(StoreItemRequest $request, CartService $cartService): JsonResponse
    {
        $cart = $cartService->addItem($request);

        $response = (new CartResource($cart))
            ->response()
            ->setStatusCode(201);

        if (! Auth::check()) {
            $response->header('X-Session-ID', $cart->session_id);
        }

        return $response;
    }

    public function update(UpdateItemRequest $request, CartItem $cartItem, CartService $cartService): CartResource
    {
        $cart = $cartService->updateItemQuantity($cartItem->id, $request->validated()['quantity']);

        return new CartResource($cart);
    }

    public function destroy(Request $request, CartItem $cartItem, CartService $cartService): JsonResponse
    {
        $user = $request->user();
        $canDelete = false;
        if ($user) {
            $canDelete = $cartItem->cart->user_id === $user->id;
        } else {
            $sessionId = $request->header('X-Session-ID');
            $canDelete = $cartItem->cart->session_id === $sessionId;
        }

        if (! $canDelete) {
            return response()->json(['message' => 'Aksi tidak diizinkan.'], 403);
        }

        $cart = $cartService->removeItem($cartItem->id);

        return response()->json([
            'message' => 'Item berhasil dihapus dari keranjang.',
            'data' => new CartResource($cart),
        ]);
    }
}

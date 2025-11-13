<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Checkout\StoreRequest;
use App\Http\Resources\OrderResource;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Order;

class CheckoutController extends Controller
{
    public function store(StoreRequest $request, CheckoutService $checkoutService): JsonResponse
    {
        try {
            $order = $checkoutService->processCheckout($request);
            $order->load('items.product', 'invitationDetail');

            return response()->json([
                'message' => 'Pesanan Anda berhasil dibuat dan menunggu pembayaran.',
                'data' => new OrderResource($order),
                'snap_token' => $order->snap_token,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function calculateShippingCost(Request $request, CheckoutService $checkoutService): JsonResponse
    {
        try {
            $cost = $checkoutService->calculateShippingCost($request);
            return response()->json($cost);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function initiateFinalPayment(Request $request, Order $order, CheckoutService $checkoutService): JsonResponse
    {
        try {
            // Ensure the user is authorized to pay for this order
            if ($request->user()->id !== $order->customer_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $snapToken = $checkoutService->initiateFinalPayment($order);

            return response()->json([
                'message' => 'Final payment initiated. Please complete the payment.',
                'snap_token' => $snapToken,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

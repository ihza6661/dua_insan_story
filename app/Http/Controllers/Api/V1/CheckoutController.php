<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Checkout\StoreRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class CheckoutController
 *
 * Handles checkout and payment operations.
 */
class CheckoutController extends Controller
{
    use AuthorizesRequests;

    /**
     * CheckoutController constructor.
     */
    public function __construct(
        protected CheckoutService $checkoutService
    ) {}

    /**
     * Process checkout and create order.
     */
    public function store(StoreRequest $request): JsonResponse
    {
        try {
            $order = $this->checkoutService->processCheckout($request);
            $order->load('items.product', 'invitationDetail');

            return response()->json([
                'message' => 'Pesanan Anda berhasil dibuat dan menunggu pembayaran.',
                'data' => OrderResource::make($order),
                'snap_token' => $order->snap_token,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Calculate shipping cost for cart items.
     */
    public function calculateShippingCost(Request $request): JsonResponse
    {
        try {
            $cost = $this->checkoutService->calculateShippingCost($request);

            return response()->json($cost);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Initiate final payment for partially paid order.
     *
     * @param  Request  $request
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function initiateFinalPayment(Order $order): JsonResponse
    {
        // Use Policy for authorization instead of manual check
        $this->authorize('pay', $order);

        try {
            $snapToken = $this->checkoutService->initiateFinalPayment($order);

            return response()->json([
                'message' => 'Final payment initiated. Please complete the payment.',
                'snap_token' => $snapToken,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}

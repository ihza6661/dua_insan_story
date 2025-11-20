<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Services\MidtransService;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Display a listing of the user's orders.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $orders = $this->orderService->getOrdersByUser($user);

        return OrderResource::collection($orders)->response();
    }

    /**
     * Display the specified order.
     *
     * @param Request $request
     * @param int $order
     * @return JsonResponse
     */
    public function show(Request $request, int $order): JsonResponse
    {
        $user = $request->user();
        $foundOrder = $this->orderService->getOrderByIdForUser($user, $order);

        if (!$foundOrder) {
            return response()->json(['message' => 'Order not found or does not belong to user.'], 404);
        }

        return (new OrderResource($foundOrder))->response();
    }

    /**
     * Retry the payment for an order that is still pending.
     *
     * @param Request $request
     * @param Order $order
     * @param MidtransService $midtransService
     * @return JsonResponse
     */
    public function retryPayment(Request $request, Order $order, MidtransService $midtransService): JsonResponse
    {
        if ($request->user()->id !== $order->customer_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if the order is in a state that allows payment retry
        $normalizedStatus = Str::snake($order->order_status);
        if (!in_array($normalizedStatus, ['pending_payment', 'failed', 'cancelled'], true)) {
            return response()->json(['message' => 'This order cannot be paid for.'], 400);
        }

        // Find the latest pending or failed payment associated with the order
        $payment = $order->payments()->whereIn('status', ['pending', 'failed', 'cancelled'])->latest()->first();

        if (!$payment) {
            return response()->json(['message' => 'No pending payment found for this order.'], 404);
        }

        // Update order status to Pending Payment
        $order->order_status = 'Pending Payment';
        $order->payment_status = 'pending';
        $order->save();

        // Generate a new Snap Token
        try {
            $snapToken = $midtransService->createTransactionToken($order, $payment);
            $payment->snap_token = $snapToken;
            $payment->status = 'pending';
            $payment->save();

            return response()->json([
                'message' => 'Payment token regenerated. Please complete the payment.',
                'snap_token' => $snapToken,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to generate payment token: ' . $e->getMessage()], 500);
        }
    }
}

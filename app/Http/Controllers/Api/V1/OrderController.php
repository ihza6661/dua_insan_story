<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CancelOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\MidtransService;
use App\Services\OrderCancellationService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Display a listing of the user's orders.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $orders = $this->orderService->getOrdersByUser($user);

        return OrderResource::collection($orders)->response();
    }

    /**
     * Display the specified order.
     */
    public function show(Request $request, int $order): JsonResponse
    {
        $user = $request->user();
        $foundOrder = $this->orderService->getOrderByIdForUser($user, $order);

        if (! $foundOrder) {
            return response()->json(['message' => 'Order not found or does not belong to user.'], 404);
        }

        return (new OrderResource($foundOrder))->response();
    }

    /**
     * Retry the payment for an order that is still pending.
     */
    public function retryPayment(Request $request, Order $order, MidtransService $midtransService): JsonResponse
    {
        if ($request->user()->id !== $order->customer_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if the order is in a state that allows payment retry
        $status = $order->order_status ?? '';
        $normalizedStatus = $status ? strtolower(str_replace(' ', '_', $status)) : '';

        // Allow empty status as it might indicate an initial state
        $allowedStatuses = ['pending_payment', 'pending', 'failed', 'cancelled', ''];

        if (! in_array($normalizedStatus, $allowedStatuses, true)) {
            return response()->json([
                'message' => 'This order cannot be paid for.',
                'current_status' => $status,
                'allowed_statuses' => $allowedStatuses,
            ], 400);
        }

        // Find the latest pending or failed payment associated with the order
        $payment = $order->payments()->whereIn('status', ['pending', 'failed', 'cancelled'])->latest()->first();

        if (! $payment) {
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
            \Illuminate\Support\Facades\Log::error('Midtrans Token Generation Failed', [
                'error' => $e->getMessage(),
                'order_id' => $order->id,
                'transaction_id' => $payment->transaction_id,
                'server_key_exists' => ! empty(config('midtrans.server_key')),
                'is_production' => config('midtrans.is_production'),
            ]);

            return response()->json(['message' => 'Failed to generate payment token: '.$e->getMessage()], 500);
        }
    }

    /**
     * Request cancellation of an order.
     */
    public function requestCancellation(
        CancelOrderRequest $request,
        Order $order,
        OrderCancellationService $cancellationService
    ): JsonResponse {
        try {
            // Check if order can be cancelled
            if (! $cancellationService->canRequestCancellation($order)) {
                return response()->json([
                    'message' => $cancellationService->getCancellationIneligibilityReason($order),
                ], 422);
            }

            // Create cancellation request
            $cancellationRequest = $cancellationService->createCancellationRequest(
                $order,
                $request->user(),
                $request->input('reason')
            );

            return response()->json([
                'message' => 'Permintaan pembatalan berhasil dibuat. Kami akan segera meninjau permintaan Anda.',
                'data' => [
                    'id' => $cancellationRequest->id,
                    'order_id' => $cancellationRequest->order_id,
                    'status' => $cancellationRequest->status,
                    'cancellation_reason' => $cancellationRequest->cancellation_reason,
                    'created_at' => $cancellationRequest->created_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal membuat permintaan pembatalan: '.$e->getMessage(),
            ], 500);
        }
    }
}

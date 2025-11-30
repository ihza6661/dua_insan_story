<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Order\UpdateStatusRequest;
use App\Http\Resources\V1\Admin\OrderResource;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Class OrderController
 *
 * Handles admin order management operations.
 */
class OrderController extends Controller
{
    use AuthorizesRequests;

    /**
     * OrderController constructor.
     */
    public function __construct(
        protected OrderRepositoryInterface $orderRepository
    ) {}

    /**
     * Display a listing of orders.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        $orders = $this->orderRepository->all([
            'customer',
            'items.product',
            'items.variant',
            'shippingAddress',
            'billingAddress',
        ]);

        return OrderResource::collection($orders);
    }

    /**
     * Display the specified order.
     *
     * @return OrderResource
     */
    public function show(Order $order)
    {
        $order->load([
            'customer',
            'items.product',
            'items.variant',
            'shippingAddress',
            'billingAddress',
            'invitationDetail',
            'payments',
        ]);

        return new OrderResource($order);
    }

    /**
     * Update the order status.
     *
     * @return OrderResource
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateStatus(UpdateStatusRequest $request, Order $order)
    {
        $this->authorize('updateStatus', $order);

        $order = $this->orderRepository->updateStatus($order, $request->input('status'));

        return new OrderResource($order);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Order\UpdateStatusRequest;
use App\Http\Resources\V1\Admin\OrderResource;
use App\Mail\OrderDelivered;
use App\Mail\OrderShipped;
use App\Mail\OrderStatusChanged;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Mail;

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

        $oldStatus = $order->order_status;
        $newStatus = $request->input('status');
        $trackingNumber = $request->input('tracking_number');

        // Update tracking number if provided
        if ($trackingNumber !== null) {
            $order->tracking_number = $trackingNumber;
        }

        $order = $this->orderRepository->updateStatus($order, $newStatus);

        // Load customer relationship for email
        $order->load(['customer', 'items.product', 'invitationDetail']);

        // Send appropriate email based on status change
        if ($oldStatus !== $newStatus && $order->customer && $order->customer->email) {
            $customerEmail = $order->customer->email;

            // Send specific email for Shipped status
            if ($newStatus === Order::STATUS_SHIPPED) {
                Mail::to($customerEmail)->send(
                    new OrderShipped($order, $order->tracking_number, $order->courier)
                );
            }
            // Send specific email for Delivered status
            elseif ($newStatus === Order::STATUS_DELIVERED) {
                Mail::to($customerEmail)->send(new OrderDelivered($order));
            }
            // Send general status change email for other statuses
            else {
                Mail::to($customerEmail)->send(new OrderStatusChanged($order, $oldStatus, $newStatus));
            }
        }

        return new OrderResource($order);
    }
}

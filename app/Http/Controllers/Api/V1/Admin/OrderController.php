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
use App\Services\NotificationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
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
        protected OrderRepositoryInterface $orderRepository,
        protected NotificationService $notificationService
    ) {}

    /**
     * Display a listing of orders.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        $filters = [
            'search' => request('search'),
            'order_status' => request('order_status'),
            'payment_status' => request('payment_status'),
            'date_from' => request('date_from'),
            'date_to' => request('date_to'),
        ];

        $perPage = (int) request('per_page', 20);

        $orders = $this->orderRepository->getPaginatedWithFilters(
            $filters,
            [
                'customer',
                'items.product',
                'items.variant',
                'shippingAddress',
                'billingAddress',
            ],
            $perPage
        );

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

        // Invalidate dashboard caches when order status changes
        Cache::flush(); // Clear all caches since dashboard keys include dynamic date ranges

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

            // Create notification for order status change
            $this->notificationService->notifyOrderStatus(
                userId: $order->customer_id,
                orderId: $order->id,
                status: $newStatus,
                orderNumber: $order->order_number
            );
        }

        return new OrderResource($order);
    }

    /**
     * Bulk update order status.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateStatus(\App\Http\Requests\Api\V1\Admin\Order\BulkUpdateStatusRequest $request)
    {
        $orderIds = $request->input('order_ids');
        $newStatus = $request->input('status');
        $trackingNumber = $request->input('tracking_number');

        $orders = Order::with(['customer', 'items.product', 'invitationDetail'])
            ->whereIn('id', $orderIds)
            ->get();
        $updatedCount = 0;
        $errors = [];

        foreach ($orders as $order) {
            try {
                $oldStatus = $order->order_status;

                // Update tracking number if provided
                if ($trackingNumber !== null) {
                    $order->tracking_number = $trackingNumber;
                }

                $order = $this->orderRepository->updateStatus($order, $newStatus);
                Cache::flush(); // Clear dashboard caches
                $updatedCount++;

                // Send appropriate email based on status change
                if ($oldStatus !== $newStatus && $order->customer && $order->customer->email) {
                    $customerEmail = $order->customer->email;

                    if ($newStatus === Order::STATUS_SHIPPED) {
                        Mail::to($customerEmail)->send(
                            new OrderShipped($order, $order->tracking_number, $order->courier)
                        );
                    } elseif ($newStatus === Order::STATUS_DELIVERED) {
                        Mail::to($customerEmail)->send(new OrderDelivered($order));
                    } else {
                        Mail::to($customerEmail)->send(new OrderStatusChanged($order, $oldStatus, $newStatus));
                    }

                    // Create notification for order status change
                    $this->notificationService->notifyOrderStatus(
                        userId: $order->customer_id,
                        orderId: $order->id,
                        status: $newStatus,
                        orderNumber: $order->order_number
                    );
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'order_id' => $order->id,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'message' => "Berhasil memperbarui {$updatedCount} pesanan.",
            'data' => [
                'updated_count' => $updatedCount,
                'total_count' => count($orderIds),
                'errors' => $errors,
            ],
        ]);
    }

    /**
     * Export orders to CSV.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(\App\Http\Requests\Api\V1\Admin\Order\ExportOrdersRequest $request)
    {
        $orderIds = $request->input('order_ids');
        $format = $request->input('format', 'csv');

        // If specific order IDs provided, export only those
        if ($orderIds && is_array($orderIds) && count($orderIds) > 0) {
            $orders = Order::with(['customer', 'items.product', 'items.variant'])
                ->whereIn('id', $orderIds)
                ->get();
        } else {
            // Otherwise, export based on filters
            $filters = [
                'search' => $request->input('search'),
                'order_status' => $request->input('order_status'),
                'payment_status' => $request->input('payment_status'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
            ];

            $orders = $this->orderRepository->getForExport($filters, [
                'customer',
                'items.product',
                'items.variant',
            ]);
        }

        $filename = 'orders_'.now()->format('Y-m-d_His').'.'.$format;

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($orders) {
            $file = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($file, [
                'Order ID',
                'Order Number',
                'Customer Name',
                'Customer Email',
                'Total Amount',
                'Amount Paid',
                'Remaining Balance',
                'Order Status',
                'Payment Status',
                'Shipping Address',
                'Shipping Cost',
                'Order Date',
                'Items',
            ]);

            // CSV Data
            foreach ($orders as $order) {
                $items = $order->items->map(function ($item) {
                    return $item->product_name.' (x'.$item->quantity.')';
                })->join('; ');

                fputcsv($file, [
                    $order->id,
                    $order->order_number,
                    $order->customer ? $order->customer->full_name : 'N/A',
                    $order->customer ? $order->customer->email : 'N/A',
                    $order->total_amount,
                    $order->amount_paid ?? 0,
                    $order->remaining_balance ?? $order->total_amount,
                    $order->order_status,
                    $order->payment_status,
                    $order->shipping_address,
                    $order->shipping_cost ?? 0,
                    $order->created_at->format('Y-m-d H:i:s'),
                    $items,
                ]);
            }

            fclose($file);
        }, 200, $headers);
    }
}

<?php

namespace App\Services;

use App\Mail\CancellationApproved;
use App\Mail\CancellationRejected;
use App\Mail\CancellationRequestAdmin;
use App\Mail\CancellationRequestReceived;
use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Service for handling order cancellation requests and approval workflow.
 */
class OrderCancellationService
{
    public function __construct(
        protected StockService $stockService,
        protected MidtransService $midtransService,
        protected ActivityLogger $activityLogger
    ) {}

    /**
     * Check if an order can be cancelled by the customer.
     * Rules:
     * - Order must be in cancellable status (Pending Payment, Partially Paid, Paid)
     * - Paid orders can only be cancelled within 24 hours
     * - Order must not have an active cancellation request
     * - Order must not already be cancelled/refunded/failed
     */
    public function canRequestCancellation(Order $order): bool
    {
        // Check if order is already in a terminal or non-cancellable state
        $nonCancellableStatuses = [
            Order::STATUS_CANCELLED,
            Order::STATUS_REFUNDED,
            Order::STATUS_FAILED,
            Order::STATUS_PROCESSING,
            Order::STATUS_COMPLETED,
            Order::STATUS_SHIPPED,
            Order::STATUS_DELIVERED,
        ];

        if (in_array($order->order_status, $nonCancellableStatuses)) {
            return false;
        }

        // Check if order has an active cancellation request
        if ($order->activeCancellationRequest) {
            return false;
        }

        // Check if order is in a cancellable status
        $cancellableStatuses = [
            Order::STATUS_PENDING_PAYMENT,
            Order::STATUS_PARTIALLY_PAID,
            Order::STATUS_PAID,
        ];

        if (! in_array($order->order_status, $cancellableStatuses)) {
            return false;
        }

        // For paid orders, check 24-hour window
        if ($order->order_status === Order::STATUS_PAID || $order->order_status === Order::STATUS_PARTIALLY_PAID) {
            $hoursSinceCreation = Carbon::parse($order->created_at)->diffInHours(now());

            if ($hoursSinceCreation > 24) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the reason why an order cannot be cancelled.
     */
    public function getCancellationIneligibilityReason(Order $order): string
    {
        $terminalStatuses = [
            Order::STATUS_CANCELLED,
            Order::STATUS_REFUNDED,
            Order::STATUS_FAILED,
        ];

        if (in_array($order->order_status, $terminalStatuses)) {
            return 'Pesanan sudah dibatalkan atau dikembalikan.';
        }

        if ($order->activeCancellationRequest) {
            return 'Pesanan sudah memiliki permintaan pembatalan yang sedang diproses.';
        }

        $cancellableStatuses = [
            Order::STATUS_PENDING_PAYMENT,
            Order::STATUS_PARTIALLY_PAID,
            Order::STATUS_PAID,
        ];

        if (! in_array($order->order_status, $cancellableStatuses)) {
            return 'Pesanan sudah dalam proses produksi dan tidak dapat dibatalkan.';
        }

        if ($order->order_status === Order::STATUS_PAID || $order->order_status === Order::STATUS_PARTIALLY_PAID) {
            $hoursSinceCreation = Carbon::parse($order->created_at)->diffInHours(now());

            if ($hoursSinceCreation > 24) {
                return 'Pesanan hanya dapat dibatalkan dalam 24 jam setelah pembayaran.';
            }
        }

        return 'Pesanan tidak memenuhi syarat pembatalan.';
    }

    /**
     * Create a cancellation request for an order.
     *
     * @throws \Exception
     */
    public function createCancellationRequest(Order $order, User $customer, string $reason): OrderCancellationRequest
    {
        if (! $this->canRequestCancellation($order)) {
            throw new \Exception($this->getCancellationIneligibilityReason($order));
        }

        return DB::transaction(function () use ($order, $customer, $reason) {
            // Create the cancellation request
            $cancellationRequest = OrderCancellationRequest::create([
                'order_id' => $order->id,
                'requested_by' => $customer->id,
                'cancellation_reason' => $reason,
                'status' => OrderCancellationRequest::STATUS_PENDING,
                'refund_amount' => $order->amount_paid > 0 ? $order->amount_paid : null,
            ]);

            Log::info('Cancellation request created', [
                'cancellation_request_id' => $cancellationRequest->id,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_id' => $customer->id,
            ]);

            // Log activity
            $this->activityLogger->logCancellationRequestCreated($cancellationRequest, $customer);

            // Send email notification to customer
            Mail::to($customer->email)->queue(new CancellationRequestReceived($order, $cancellationRequest));

            // Send email notification to admin
            $adminEmails = $this->getAdminEmails();
            foreach ($adminEmails as $adminEmail) {
                Mail::to($adminEmail)->queue(new CancellationRequestAdmin($order, $cancellationRequest));
            }

            return $cancellationRequest;
        });
    }

    /**
     * Approve a cancellation request.
     * This will:
     * 1. Update the cancellation request status
     * 2. Update the order status to Cancelled
     * 3. Restore stock
     * 4. Initiate refund if payment was made
     * 5. Send email notifications
     *
     * @throws \Exception
     */
    public function approveCancellation(OrderCancellationRequest $cancellationRequest, User $admin, ?string $notes = null): void
    {
        if (! $cancellationRequest->isPending()) {
            throw new \Exception('Only pending cancellation requests can be approved.');
        }

        DB::transaction(function () use ($cancellationRequest, $admin, $notes) {
            $order = $cancellationRequest->order;

            // Mark cancellation request as approved
            $cancellationRequest->approve($admin, $notes);

            // Update order status to Cancelled
            $order->order_status = Order::STATUS_CANCELLED;
            $order->save();

            // Restore stock
            if ($this->stockService->restoreStockForOrder($order)) {
                $cancellationRequest->update(['stock_restored' => true]);
            }

            // Initiate refund if payment was made
            $paidPayment = $order->payments()->where('status', 'paid')->exists();
            if ($paidPayment) {
                $this->initiateRefund($cancellationRequest, $order);
            }

            Log::info('Cancellation request approved', [
                'cancellation_request_id' => $cancellationRequest->id,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'approved_by' => $admin->id,
            ]);

            // Log activity
            $this->activityLogger->logCancellationApproved(
                $cancellationRequest,
                $admin,
                $notes,
                $cancellationRequest->refund_amount
            );

            // Send email notification to customer
            Mail::to($order->customer->email)->queue(new CancellationApproved($order, $cancellationRequest));
        });
    }

    /**
     * Reject a cancellation request.
     *
     * @throws \Exception
     */
    public function rejectCancellation(OrderCancellationRequest $cancellationRequest, User $admin, string $notes): void
    {
        if (! $cancellationRequest->isPending()) {
            throw new \Exception('Only pending cancellation requests can be rejected.');
        }

        DB::transaction(function () use ($cancellationRequest, $admin, $notes) {
            $order = $cancellationRequest->order;

            // Mark cancellation request as rejected
            $cancellationRequest->reject($admin, $notes);

            Log::info('Cancellation request rejected', [
                'cancellation_request_id' => $cancellationRequest->id,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'rejected_by' => $admin->id,
            ]);

            // Log activity
            $this->activityLogger->logCancellationRejected($cancellationRequest, $admin, $notes);

            // Send email notification to customer
            Mail::to($order->customer->email)->queue(new CancellationRejected($order, $cancellationRequest));
        });
    }

    /**
     * Initiate refund for a cancelled order.
     * Note: This creates a refund record and marks it as manual process.
     * Admin will need to process the refund through Midtrans dashboard.
     */
    protected function initiateRefund(OrderCancellationRequest $cancellationRequest, Order $order): void
    {
        try {
            // Get the latest paid payment
            $payment = $order->payments()->where('status', 'paid')->latest()->first();

            if (! $payment) {
                Log::warning('No paid payment found for refund', [
                    'order_id' => $order->id,
                ]);

                return;
            }

            // Mark refund as initiated (manual process)
            $cancellationRequest->update([
                'refund_initiated' => true,
                'refund_status' => OrderCancellationRequest::REFUND_STATUS_PENDING,
                'refund_amount' => $payment->amount,
            ]);

            Log::info('Refund initiated for cancellation', [
                'cancellation_request_id' => $cancellationRequest->id,
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'refund_amount' => $payment->amount,
            ]);

            // Note: Admin needs to process refund through Midtrans dashboard
            // When Midtrans sends refund webhook, we'll update the refund_status to 'completed'
        } catch (\Exception $e) {
            Log::error('Failed to initiate refund', [
                'cancellation_request_id' => $cancellationRequest->id,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            $cancellationRequest->update([
                'refund_initiated' => true,
                'refund_status' => OrderCancellationRequest::REFUND_STATUS_FAILED,
            ]);
        }
    }

    /**
     * Get predefined cancellation reasons.
     */
    public function getCancellationReasons(): array
    {
        return [
            'Berubah pikiran',
            'Menemukan harga lebih murah di tempat lain',
            'Pengiriman terlalu lama',
            'Salah memesan produk',
            'Masalah pembayaran',
            'Alasan pribadi',
            'Lainnya',
        ];
    }

    /**
     * Get admin email addresses for notifications.
     */
    protected function getAdminEmails(): array
    {
        // Get admin users
        $admins = User::where('role', 'admin')->get();

        return $admins->pluck('email')->toArray();
    }

    /**
     * Get cancellation policy text.
     */
    public function getCancellationPolicy(): array
    {
        return [
            'title' => 'Kebijakan Pembatalan Pesanan',
            'rules' => [
                'Pesanan yang belum dibayar dapat dibatalkan kapan saja.',
                'Pesanan yang sudah dibayar hanya dapat dibatalkan dalam waktu 24 jam setelah pembayaran.',
                'Pesanan yang sudah dalam proses produksi tidak dapat dibatalkan.',
                'Pengembalian dana akan diproses dalam waktu 5-10 hari kerja setelah pembatalan disetujui.',
                'Pengembalian dana akan dikembalikan ke metode pembayaran yang sama.',
            ],
            'non_cancellable_statuses' => [
                'Processing' => 'Pesanan sedang diproses',
                'Design Approval' => 'Menunggu persetujuan desain',
                'In Production' => 'Dalam produksi',
                'Shipped' => 'Pesanan sudah dikirim',
                'Delivered' => 'Pesanan sudah diterima',
                'Completed' => 'Pesanan selesai',
            ],
        ];
    }
}

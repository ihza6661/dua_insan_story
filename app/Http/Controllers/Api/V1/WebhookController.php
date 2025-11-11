<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CheckoutService;
use App\Models\Order;
use App\Models\Payment;

class WebhookController extends Controller
{
    protected $checkoutService;

    public function __construct(CheckoutService $checkoutService)
    {
        $this->checkoutService = $checkoutService;
    }

    public function midtrans(Request $request)
    {
        try {
            $payload = $request->all();

            // Log the incoming payload for debugging
            \Log::info('Midtrans Webhook Payload:', $payload);

            if (!isset($payload['order_id'], $payload['status_code'], $payload['gross_amount'], $payload['signature_key'])) {
                \Log::warning('Midtrans Webhook: Missing required fields.', ['payload' => $payload]);
                return response()->json(['status' => 'error', 'message' => 'Invalid payload'], 400);
            }

            // Validate signature key
            $signatureKey = hash('sha512', $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . config('midtrans.server_key'));

            \Log::info('Midtrans Signature Validation:', [
                'generated_signature' => $signatureKey,
                'payload_signature' => $payload['signature_key'],
            ]);

            if (!hash_equals($signatureKey, $payload['signature_key'])) {
                \Log::warning('Midtrans Webhook: Invalid signature key.', ['payload' => $payload]);
                return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
            }

            $payment = $this->resolvePayment($payload['order_id']);

            if (!$payment) {
                \Log::warning('Midtrans Webhook: Payment not found.', ['order_id' => $payload['order_id']]);
                return response()->json(['status' => 'error', 'message' => 'Payment not found'], 404);
            }

            $order = $payment->order;

            if (!$order) {
                \Log::warning('Midtrans Webhook: Order missing for payment.', [
                    'order_id' => $payload['order_id'],
                    'payment_id' => $payment->id,
                ]);
                return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
            }

            $transactionStatus = $payload['transaction_status'] ?? null;
            $fraudStatus = $payload['fraud_status'] ?? null;

            switch ($transactionStatus) {
                case 'capture':
                case 'settlement':
                    if ($fraudStatus === 'accept') {
                        $this->handleSuccessfulPayment($order, $payment, $payload);
                        \Log::info('Midtrans Webhook: Payment settled.', ['order_id' => $order->id, 'payment_id' => $payment->id]);
                    } elseif ($fraudStatus === 'challenge') {
                        $this->updatePaymentStatus($payment, 'pending', $payload);
                        $this->updateOrderStatus($order, 'Pending Payment', 'pending');
                        \Log::info('Midtrans Webhook: Payment challenged, marked pending.', ['order_id' => $order->id, 'payment_id' => $payment->id]);
                    } else {
                        $this->updatePaymentStatus($payment, 'failed', $payload);
                        $this->updateOrderStatus($order, 'Failed', 'failed');
                        \Log::info('Midtrans Webhook: Payment rejected after capture.', ['order_id' => $order->id, 'payment_id' => $payment->id]);
                    }
                    break;
                case 'pending':
                    $this->updatePaymentStatus($payment, 'pending', $payload);
                    $this->updateOrderStatus($order, 'Pending Payment', 'pending');
                    \Log::info('Midtrans Webhook: Order and payment marked pending.', ['order_id' => $order->id, 'payment_id' => $payment->id]);
                    break;
                case 'deny':
                    $this->updatePaymentStatus($payment, 'failed', $payload);
                    $this->updateOrderStatus($order, 'Failed', 'failed');
                    \Log::info('Midtrans Webhook: Order and payment marked failed (deny).', ['order_id' => $order->id, 'payment_id' => $payment->id]);
                    break;
                case 'cancel':
                case 'expire':
                    $this->updatePaymentStatus($payment, 'cancelled', $payload);
                    $this->updateOrderStatus($order, 'Cancelled', 'cancelled');
                    \Log::info('Midtrans Webhook: Order and payment marked cancelled/expired.', ['order_id' => $order->id, 'payment_id' => $payment->id]);
                    break;
                case 'refund':
                    $this->updatePaymentStatus($payment, 'refunded', $payload);
                    $this->updateOrderStatus($order, 'Refunded', 'refunded');
                    \Log::info('Midtrans Webhook: Order and payment marked refunded.', ['order_id' => $order->id, 'payment_id' => $payment->id]);
                    break;
                default:
                    \Log::info('Midtrans Webhook: Unhandled transaction status.', [
                        'order_id' => $order->id,
                        'payment_id' => $payment->id,
                        'transaction_status' => $transactionStatus,
                        'fraud_status' => $fraudStatus,
                    ]);
                    break;
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            \Log::error('Midtrans Webhook Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);
            return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
        }
    }

    private function resolvePayment(string $midtransOrderId): ?Payment
    {
        $payment = Payment::where('transaction_id', $midtransOrderId)->first();

        if ($payment) {
            return $payment;
        }

        if (str_contains($midtransOrderId, '-')) {
            $segments = explode('-', $midtransOrderId);
            $candidateId = $segments[0];

            if (ctype_digit($candidateId)) {
                return Payment::find((int) $candidateId);
            }
        }

        return null;
    }

    private function handleSuccessfulPayment(Order $order, Payment $payment, array $payload): void
    {
        if ($payment->status === 'paid') {
            \Log::info('Midtrans Webhook: Payment already processed, skipping duplicate.', [
                'payment_id' => $payment->id,
            ]);

            return;
        }

    $this->updatePaymentStatus($payment, 'paid', $payload);

    $isDownPayment = $payment->payment_type === 'dp';
    $orderStatus = $isDownPayment ? 'Partially Paid' : 'Paid';
    $paymentStatus = $isDownPayment ? 'partially_paid' : 'paid';

    $this->updateOrderStatus($order, $orderStatus, $paymentStatus);

        if ($order->customer) {
            $this->checkoutService->clearCart($order->customer);
        }
    }

    private function updatePaymentStatus(Payment $payment, string $status, array $payload): void
    {
        $payment->status = $status;
        $payment->raw_response = $payload;
        $payment->save();
    }

    private function updateOrderStatus(Order $order, string $orderStatus, string $paymentStatus): void
    {
        $order->order_status = $orderStatus;
        $order->payment_status = $paymentStatus;
        $order->save();
    }
}

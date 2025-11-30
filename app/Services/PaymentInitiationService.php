<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

/**
 * Class PaymentInitiationService
 *
 * Handles payment initiation and Midtrans integration.
 */
class PaymentInitiationService
{
    /**
     * PaymentInitiationService constructor.
     */
    public function __construct(
        protected MidtransService $midtransService
    ) {}

    /**
     * Handle initial payment creation (DP or Full Payment).
     *
     * @param  string  $paymentOption  'dp' or 'full'
     */
    public function initiatePayment(Order $order, string $paymentOption = 'full'): Payment
    {
        $paymentAmount = $this->calculatePaymentAmount($order, $paymentOption);
        $paymentType = $paymentOption === 'dp' ? 'dp' : 'full';

        // Create the payment record
        $payment = $order->payments()->create([
            'transaction_id' => Str::uuid()->toString(),
            'amount' => $paymentAmount,
            'status' => 'pending',
            'payment_type' => $paymentType,
        ]);

        // Generate Midtrans Snap Token
        $snapToken = $this->midtransService->createTransactionToken($order, $payment);

        // Attach Snap Token to the payment record
        $payment->snap_token = $snapToken;
        $payment->save();

        // Also attach to order for easier access
        $order->snap_token = $snapToken;
        $order->save();

        return $payment;
    }

    /**
     * Initiates the final payment for an order that was partially paid.
     *
     * @return string The Snap Token for the final payment.
     *
     * @throws \Exception
     */
    public function initiateFinalPayment(Order $order): string
    {
        if ($order->order_status !== 'Partially Paid') {
            throw new \Exception('Order is not awaiting final payment.');
        }

        $remainingAmount = $this->calculateRemainingBalance($order);

        if ($remainingAmount <= 0) {
            throw new \Exception('No remaining balance to be paid.');
        }

        // Create the final payment record
        $finalPayment = $order->payments()->create([
            'transaction_id' => Str::uuid()->toString(),
            'amount' => $remainingAmount,
            'status' => 'pending',
            'payment_type' => 'final',
        ]);

        // Generate a new Snap Token for the remaining amount
        $snapToken = $this->midtransService->createTransactionToken($order, $finalPayment);

        $finalPayment->snap_token = $snapToken;
        $finalPayment->save();

        return $snapToken;
    }

    /**
     * Calculate payment amount based on option.
     */
    protected function calculatePaymentAmount(Order $order, string $paymentOption): float
    {
        if ($paymentOption === 'dp') {
            $downPaymentRate = Config::get('payments.down_payment_rate', 0.5);

            return $order->total_amount * $downPaymentRate;
        }

        return $order->total_amount;
    }

    /**
     * Calculate remaining balance for an order.
     */
    protected function calculateRemainingBalance(Order $order): float
    {
        $paidAmount = $order->payments()->where('status', 'paid')->sum('amount');

        return $order->total_amount - $paidAmount;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\DigitalInvitationService;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected $midtransService;

    protected $digitalInvitationService;

    public function __construct(
        MidtransService $midtransService,
        DigitalInvitationService $digitalInvitationService
    ) {
        $this->midtransService = $midtransService;
        $this->digitalInvitationService = $digitalInvitationService;
    }

    public function midtrans(Request $request)
    {
        $notification = $this->midtransService->handleNotification($request->all());

        $orderId = $notification->order_id;
        $transactionStatus = $notification->transaction_status;
        $fraudStatus = $notification->fraud_status;
        $paymentType = $notification->payment_type;

        // The order_id from midtrans is the payment_id in our system
        $paymentId = explode('-', $orderId)[0];
        $payment = Payment::find($paymentId);

        if (! $payment) {
            Log::error('Payment not found for ID: '.$paymentId);

            return response()->json(['error' => 'Payment not found'], 404);
        }

        $order = $payment->order;

        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'accept') {
                // Payment is successful
                $this->handleSuccessfulPayment($payment, $order);
            }
        } elseif ($transactionStatus == 'settlement') {
            // Payment is settled
            $this->handleSuccessfulPayment($payment, $order);
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
            // Payment failed
            $payment->status = 'failed';
            $payment->save();
            $order->order_status = 'Failed';
            $order->save();
        }

        return response()->json(['status' => 'ok']);
    }

    private function handleSuccessfulPayment(Payment $payment, Order $order)
    {
        if ($payment->status === 'success') {
            // Already processed
            return;
        }

        $payment->status = 'success';
        $payment->save();

        if ($payment->payment_type == 'dp') {
            $order->order_status = 'Partially Paid';
            $order->save();

            // Create the final payment record
            $this->createFinalPayment($order);

        } elseif ($payment->payment_type == 'full' || $payment->payment_type == 'final') {
            $order->order_status = 'Paid';
            $order->save();
            
            // Auto-create digital invitation if order contains digital products
            try {
                $this->digitalInvitationService->createFromOrder($order);
            } catch (\Exception $e) {
                Log::error('Failed to create digital invitation: '.$e->getMessage(), [
                    'order_id' => $order->id,
                ]);
            }
            
            // Optionally, you can move it to 'Processing' immediately
            // $order->order_status = 'Processing';
            // $order->save();
        }
    }

    private function createFinalPayment(Order $order)
    {
        $paidAmount = $order->payments()->where('status', 'success')->sum('amount');
        $remainingAmount = $order->total_amount - $paidAmount;

        if ($remainingAmount > 0) {
            $finalPayment = $order->payments()->create([
                'amount' => $remainingAmount,
                'status' => 'pending',
                'payment_type' => 'final',
            ]);

            // Note: We don't generate a snap token here.
            // The user will have to initiate the final payment from their order page.
        }
    }
}

<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Notification;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        $this->configureMidtrans();
    }

    public function configureMidtrans()
    {
        // Fetch settings directly from DB to avoid config caching issues
        // and ensure we always have the latest values.

        $serverKey = \App\Models\Setting::where('key', 'midtrans_server_key')->value('value');
        $mode = \App\Models\Setting::where('key', 'payment_gateway_mode')->value('value');

        // Fallback to config/env if DB values are missing
        $serverKey = $serverKey ?: config('midtrans.server_key');
        $isProduction = ($mode === 'production') ?: config('midtrans.is_production');

        Config::$serverKey = $serverKey;
        Config::$isProduction = $isProduction;
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
        Config::$overrideNotifUrl = config('midtrans.notification_url');
    }

    public function handleNotification(array $notificationPayload)
    {
        return new Notification($notificationPayload);
    }

    public function createTransactionToken(Order $order, Payment $payment)
    {
        $this->configureMidtrans();

        // Generate a new unique Transaction ID for every attempt.
        // This is crucial for retries, as Midtrans does not allow reusing the same Order ID
        // if the previous attempt failed or is still pending in their system.
        // We append a short random suffix (4 chars) to the Order Number.
        $suffix = strtoupper(Str::random(4));
        $newTransactionId = $order->order_number.'-'.$suffix;

        // Update the payment record with the new transaction ID so we can match the webhook later.
        $payment->transaction_id = $newTransactionId;
        $payment->save();

        $params = [
            'transaction_details' => [
                'order_id' => $newTransactionId,
                'gross_amount' => (int) $payment->amount, // Ensure integer for safety
            ],
            'customer_details' => [
                'first_name' => $order->customer->full_name ?? 'Customer',
                'email' => $order->customer->email,
                'phone' => $order->customer->phone_number ?? '',
            ],
            'item_details' => $this->buildItemDetails($order, $payment),
        ];

        Log::info('Midtrans createTransactionToken', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'transaction_id' => $newTransactionId,
            'amount' => $payment->amount,
            'server_key_set' => !empty(Config::$serverKey),
            'notification_url' => Config::$overrideNotifUrl,
            'is_production' => Config::$isProduction,
            'params' => $params,
        ]);

        try {
            $snapToken = Snap::getSnapToken($params);
            Log::info('Midtrans snap token created successfully', [
                'transaction_id' => $newTransactionId,
            ]);
            return $snapToken;
        } catch (\Exception $e) {
            Log::error('Midtrans createTransactionToken failed', [
                'order_id' => $order->id,
                'transaction_id' => $newTransactionId,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Build item details for Midtrans.
     * We use a simplified single-item approach to ensure the sum of items exactly matches the gross amount.
     * This prevents "Transaction not found" errors caused by rounding mismatches or shipping cost calculations.
     */
    private function buildItemDetails(Order $order, Payment $payment): array
    {
        $itemName = 'Payment';
        $itemIdPrefix = 'PAY';

        if ($payment->payment_type === 'dp') {
            $itemName = 'Down Payment';
            $itemIdPrefix = 'DP';
        } elseif ($payment->payment_type === 'final') {
            $itemName = 'Final Payment';
            $itemIdPrefix = 'FINAL';
        } else {
            $itemName = 'Full Payment';
            $itemIdPrefix = 'FULL';
        }

        return [
            [
                'id' => $itemIdPrefix.'-'.$order->order_number,
                'price' => (int) $payment->amount,
                'quantity' => 1,
                'name' => $itemName.' ('.$order->order_number.')',
            ],
        ];
    }
}

<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Midtrans\Snap;
use Midtrans\Config;
use Midtrans\Notification;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
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
        $orderId = $payment->transaction_id;

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $payment->amount,
            ],
            'customer_details' => [
                'first_name' => $order->customer->name,
                'email' => $order->customer->email,
                'phone' => $order->customer->phone,
            ],
            'item_details' => $this->buildItemDetails($order, $payment),
        ];

        return Snap::getSnapToken($params);
    }

    /**
     * Build item details for Midtrans based on payment type.
     *
     * @param Order $order
     * @param Payment $payment
     * @return array
     */
    private function buildItemDetails(Order $order, Payment $payment): array
    {
        if ($payment->payment_type === 'dp') {
            return [
                [
                    'id' => 'DP-' . $order->order_number,
                    'price' => $payment->amount,
                    'quantity' => 1,
                    'name' => 'Down Payment (' . $order->order_number . ')',
                ],
            ];
        }

        if ($payment->payment_type === 'final') {
            return [
                [
                    'id' => 'FINAL-' . $order->order_number,
                    'price' => $payment->amount,
                    'quantity' => 1,
                    'name' => 'Final Payment (' . $order->order_number . ')',
                ],
            ];
        }

        // For full payment, list all items
        $items = $order->items->map(function ($item) {
            return [
                'id' => $item->product_variant_id ?? $item->product_id,
                'price' => $item->unit_price,
                'quantity' => $item->quantity,
                'name' => $item->product->name,
            ];
        })->toArray();

        if ($order->shipping_cost > 0) {
            $items[] = [
                'id' => 'SHIPPING',
                'price' => $order->shipping_cost,
                'quantity' => 1,
                'name' => 'Shipping Cost',
            ];
        }

        return $items;
    }
}
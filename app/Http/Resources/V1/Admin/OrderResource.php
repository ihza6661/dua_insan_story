<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->customer_id,
            'user_full_name' => $this->customer?->full_name,
            'order_status' => $this->order_status,
            'payment_status' => $this->payment_status,
            'total_amount' => $this->total_amount,
            'amount_paid' => $this->amount_paid,
            'remaining_balance' => $this->remaining_balance,
            'created_at' => $this->created_at,
            'shipping_address' => $this->shipping_address,
            'shipping_method' => $this->shipping_method,
            'shipping_cost' => $this->shipping_cost,
            'shipping_service' => $this->shipping_service,
            'courier' => $this->courier,
            'billing_address' => $this->billingAddress?->full_address,
            'order_items' => OrderItemResource::collection($this->whenLoaded('items')),
            'invitation_detail' => new InvitationDetailResource($this->whenLoaded('invitationDetail')),
            'payments' => $this->whenLoaded('payments', function () {
                return $this->payments->map(function ($payment) {
                    $method = $payment->payment_gateway;

                    if (! empty($payment->raw_response) && isset($payment->raw_response['payment_type'])) {
                        $type = $payment->raw_response['payment_type'];
                        $method = ucwords(str_replace('_', ' ', $type));

                        if ($type === 'bank_transfer') {
                            if (isset($payment->raw_response['va_numbers'][0]['bank'])) {
                                $method .= ' - '.strtoupper($payment->raw_response['va_numbers'][0]['bank']);
                            } elseif (isset($payment->raw_response['permata_va_number'])) {
                                $method .= ' - PERMATA';
                            }
                        } elseif ($type === 'cstore' && isset($payment->raw_response['store'])) {
                            $method .= ' - '.ucfirst($payment->raw_response['store']);
                        }
                    }

                    return [
                        'id' => $payment->id,
                        'payment_date' => $payment->created_at,
                        'amount' => $payment->amount,
                        'payment_method' => $method,
                        'status' => $payment->status,
                    ];
                });
            }),
        ];
    }
}

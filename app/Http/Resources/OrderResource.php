<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $payments = $this->relationLoaded('payments')
            ? $this->payments
            : $this->payments()->get();

        $paidAmount = $payments->where('status', 'paid')->sum('amount');
        $sortedPayments = $payments->sortBy('created_at');
        $initialPayment = $sortedPayments->first();
        $latestPayment = $payments->sortByDesc('created_at')->first();
        $paymentStatus = $this->payment_status ?? ($latestPayment->status ?? 'pending');
        $paymentOption = $initialPayment->payment_type ?? null;
        $remainingBalance = max(0, (float) $this->total_amount - $paidAmount);

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'total_amount' => $this->total_amount,
            'shipping_address' => $this->shipping_address,
            'order_status' => $this->order_status,
            'payment_status' => $paymentStatus,
            'payment_option' => $paymentOption,
            'amount_paid' => (float) $paidAmount,
            'remaining_balance' => (float) $remainingBalance,
            'created_at' => $this->created_at->toDateTimeString(),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'custom_data' => new InvitationDetailResource($this->whenLoaded('invitationDetail')),
        ];
    }
}

<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V1\Admin\InvitationDetailResource;

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
            'billing_address' => $this->billingAddress?->full_address,
            'order_items' => OrderItemResource::collection($this->whenLoaded('items')),
            'invitation_detail' => new InvitationDetailResource($this->whenLoaded('invitationDetail')),
        ];
    }
}
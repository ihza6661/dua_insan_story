<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'role' => $this->role,
            'address' => $this->whenLoaded('address', function () {
                return $this->address?->street;
            }, null),
            'province_name' => $this->whenLoaded('address', function () {
                return $this->address?->state;
            }, null),
            'city_name' => $this->whenLoaded('address', function () {
                return $this->address?->city;
            }, null),
            'postal_code' => $this->whenLoaded('address', function () {
                return $this->address?->postal_code;
            }, null),
        ];
    }
}

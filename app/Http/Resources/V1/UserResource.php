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
            $this->mergeWhen($this->whenLoaded('address'), [
                'address' => $this->address->street ?? null,
                'province_name' => $this->address->state ?? null,
                'city_name' => $this->address->city ?? null,
                'postal_code' => $this->address->postal_code ?? null,
            ]),
        ];
    }
}

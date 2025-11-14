<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'bride_full_name' => $this->bride_full_name,
            'groom_full_name' => $this->groom_full_name,
            'bride_nickname' => $this->bride_nickname,
            'groom_nickname' => $this->groom_nickname,
            'bride_parents' => $this->bride_parents,
            'groom_parents' => $this->groom_parents,
            'akad_date' => $this->akad_date,
            'akad_time' => $this->akad_time,
            'akad_location' => $this->akad_location,
            'reception_date' => $this->reception_date,
            'reception_time' => $this->reception_time,
            'reception_location' => $this->reception_location,
            'gmaps_link' => $this->gmaps_link,
            'prewedding_photo' => $this->prewedding_photo_path ? asset($this->prewedding_photo_path) : null,
        ];
    }
}

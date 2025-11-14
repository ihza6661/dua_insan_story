<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'bride_full_name' => $this->bride_full_name,
            'groom_full_name' => $this->groom_full_name,
            'bride_nickname' => $this->bride_nickname,
            'groom_nickname' => $this->groom_nickname,
            'bride_parents' => $this->bride_parents,
            'groom_parents' => $this->groom_parents,
            'akad_date' => $this->akad_date->format('Y-m-d'),
            'akad_time' => $this->akad_time,
            'akad_location' => $this->akad_location,
            'reception_date' => $this->reception_date->format('Y-m-d'),
            'reception_time' => $this->reception_time,
            'reception_location' => $this->reception_location,
            'gmaps_link' => $this->gmaps_link,
            'prewedding_photo_url' => $this->prewedding_photo_path ? asset($this->prewedding_photo_path) : null,
        ];
    }
}

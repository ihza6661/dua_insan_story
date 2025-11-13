<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddOnResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        $data['weight'] = $this->weight;

        if (isset($data['pivot']) && $this->pivot) {
            $data['pivot']['weight'] = $this->pivot->weight;
        }

        return $data;
    }
}

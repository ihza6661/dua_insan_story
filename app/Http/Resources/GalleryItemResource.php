<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class GalleryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'media_type' => $this->media_type,
            'file_url' => Storage::disk('public')->url($this->file_url),
            'product' => new ProductResource($this->whenLoaded('product')),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}

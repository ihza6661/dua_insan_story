<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\GalleryItemResource;
use App\Services\Customer\GalleryItemService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GalleryItemController extends Controller
{
    protected $galleryItemService;

    public function __construct(GalleryItemService $galleryItemService)
    {
        $this->galleryItemService = $galleryItemService;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $category = $request->query('category');
        $galleryItems = $this->galleryItemService->getPaginatedItems($category);

        return GalleryItemResource::collection($galleryItems);
    }

    public function show(string $id): GalleryItemResource
    {
        $galleryItem = $this->galleryItemService->findItemById((int) $id);
        $galleryItem->load('product');

        return new GalleryItemResource($galleryItem);
    }
}

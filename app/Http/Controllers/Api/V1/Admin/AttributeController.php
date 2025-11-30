<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Attribute\StoreRequest;
use App\Http\Requests\Api\V1\Admin\Attribute\UpdateRequest;
use App\Http\Resources\AttributeResource;
use App\Models\Attribute;
use App\Services\AttributeService;
use Illuminate\Http\JsonResponse;

class AttributeController extends Controller
{
    protected $attributeService;

    public function __construct(AttributeService $attributeService)
    {
        $this->attributeService = $attributeService;
    }

    public function index()
    {
        return AttributeResource::collection(Attribute::with('attributeValues')->get());
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $attribute = $this->attributeService->createAttribute($request->validated());

        return response()->json([
            'message' => 'Atribut berhasil dibuat.',
            'data' => new AttributeResource($attribute),
        ], 201);
    }

    public function show(Attribute $attribute): AttributeResource
    {
        $attribute->load('attributeValues');

        return new AttributeResource($attribute);
    }

    public function update(UpdateRequest $request, Attribute $attribute): JsonResponse
    {
        $updatedAttribute = $this->attributeService->updateAttribute($attribute, $request->validated());

        return response()->json([
            'message' => 'Atribut berhasil diperbarui.',
            'data' => new AttributeResource($updatedAttribute),
        ]);
    }

    public function destroy(Attribute $attribute): JsonResponse
    {
        try {
            $this->attributeService->deleteAttribute($attribute);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json(['message' => 'Atribut berhasil dihapus.']);
    }
}

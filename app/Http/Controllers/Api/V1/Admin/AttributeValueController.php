<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\AttributeValue\StoreRequest;
use App\Http\Requests\Api\V1\Admin\AttributeValue\UpdateRequest;
use App\Http\Resources\AttributeValueResource;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Services\AttributeValueService;
use Illuminate\Http\JsonResponse;

class AttributeValueController extends Controller
{
    protected $attributeValueService;

    public function __construct(AttributeValueService $attributeValueService)
    {
        $this->attributeValueService = $attributeValueService;
    }

    public function store(StoreRequest $request, Attribute $attribute): JsonResponse
    {
        $value = $this->attributeValueService->createAttributeValue($attribute, $request->validated());

        return response()->json([
            'message' => 'Nilai atribut berhasil dibuat.',
            'data' => new AttributeValueResource($value),
        ], 201);
    }

    public function update(UpdateRequest $request, AttributeValue $value): JsonResponse
    {
        $updatedValue = $this->attributeValueService->updateAttributeValue($value, $request->validated());

        return response()->json([
            'message' => 'Nilai atribut berhasil diperbarui.',
            'data' => new AttributeValueResource($updatedValue),
        ]);
    }

    public function destroy(AttributeValue $value): JsonResponse
    {
        try {
            $this->attributeValueService->deleteAttributeValue($value);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json(['message' => 'Nilai atribut berhasil dihapus.']);
    }
}

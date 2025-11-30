<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\AddOn\StoreRequest;
use App\Http\Requests\Api\V1\Admin\AddOn\UpdateRequest;
use App\Http\Resources\AddOnResource;
use App\Models\AddOn;
use App\Services\AddOnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AddOnController extends Controller
{
    protected $addOnService;

    public function __construct(AddOnService $addOnService)
    {
        $this->addOnService = $addOnService;
    }

    public function index(): AnonymousResourceCollection
    {
        return AddOnResource::collection(AddOn::latest()->get());
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $addOn = $this->addOnService->createAddOn($request->validated());

        return response()->json([
            'message' => 'Item tambahan berhasil dibuat.',
            'data' => new AddOnResource($addOn),
        ], 201);
    }

    public function show(AddOn $addOn): AddOnResource
    {
        return new AddOnResource($addOn);
    }

    public function update(UpdateRequest $request, AddOn $addOn): JsonResponse
    {
        $updatedAddOn = $this->addOnService->updateAddOn($addOn, $request->validated());

        return response()->json([
            'message' => 'Item tambahan berhasil diperbarui.',
            'data' => new AddOnResource($updatedAddOn),
        ]);
    }

    public function destroy(AddOn $addOn): JsonResponse
    {
        try {
            $this->addOnService->deleteAddOn($addOn);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 409); // 409 Conflict
        }

        return response()->json(['message' => 'Item tambahan berhasil dihapus.']);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\GetDesignProofsRequest;
use App\Http\Requests\Api\Admin\StoreDesignProofRequest;
use App\Models\DesignProof;
use App\Models\OrderItem;
use App\Services\DesignProofService;
use Illuminate\Http\JsonResponse;

class DesignProofController extends Controller
{
    public function __construct(
        protected DesignProofService $designProofService
    ) {}

    /**
     * Get all design proofs for an order
     */
    public function index(GetDesignProofsRequest $request): JsonResponse
    {
        $designProofs = $this->designProofService->getDesignProofsForOrder(
            $request->validated('order_id')
        );

        return response()->json([
            'message' => 'Design proofs retrieved successfully',
            'data' => $designProofs,
        ]);
    }

    /**
     * Upload a new design proof
     */
    public function store(StoreDesignProofRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $orderItem = OrderItem::findOrFail($validated['order_item_id']);

            $designProof = $this->designProofService->uploadDesignProof(
                $orderItem,
                $request->file('file'),
                $request->user(),
                $validated['admin_notes'] ?? null
            );

            return response()->json([
                'message' => 'Design proof uploaded successfully',
                'data' => $designProof->load(['uploadedBy', 'orderItem.product']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload design proof',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a specific design proof
     */
    public function show(DesignProof $designProof): JsonResponse
    {
        return response()->json([
            'message' => 'Design proof retrieved successfully',
            'data' => $designProof->load(['orderItem.product', 'uploadedBy', 'reviewedBy']),
        ]);
    }

    /**
     * Delete a design proof
     */
    public function destroy(DesignProof $designProof): JsonResponse
    {
        try {
            $this->designProofService->deleteDesignProof($designProof);

            return response()->json([
                'message' => 'Design proof deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete design proof',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get design proofs for a specific order item
     */
    public function getByOrderItem(OrderItem $orderItem): JsonResponse
    {
        $designProofs = $orderItem->designProofs()
            ->with(['uploadedBy', 'reviewedBy'])
            ->orderBy('version', 'desc')
            ->get();

        return response()->json([
            'message' => 'Design proofs retrieved successfully',
            'data' => $designProofs,
        ]);
    }
}

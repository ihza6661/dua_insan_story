<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\DesignProof;
use App\Models\OrderItem;
use App\Services\DesignProofService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DesignProofController extends Controller
{
    public function __construct(
        protected DesignProofService $designProofService
    ) {}

    /**
     * Get all design proofs for an order
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $designProofs = $this->designProofService->getDesignProofsForOrder(
            $request->input('order_id')
        );

        return response()->json([
            'message' => 'Design proofs retrieved successfully',
            'data' => $designProofs,
        ]);
    }

    /**
     * Upload a new design proof
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_item_id' => 'required|exists:order_items,id',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240', // Max 10MB
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $orderItem = OrderItem::findOrFail($request->input('order_item_id'));
            
            $designProof = $this->designProofService->uploadDesignProof(
                $orderItem,
                $request->file('file'),
                $request->user(),
                $request->input('admin_notes')
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

<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\DesignProof;
use App\Models\Order;
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
     * Get all design proofs for customer's orders
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $designProofs = DesignProof::whereHas('orderItem.order', function ($query) use ($user) {
            $query->where('customer_id', $user->id);
        })
            ->with(['orderItem.product', 'orderItem.order', 'uploadedBy', 'reviewedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Design proofs retrieved successfully',
            'data' => $designProofs,
        ]);
    }

    /**
     * Get design proofs for a specific order
     */
    public function getByOrder(Request $request, Order $order): JsonResponse
    {
        // Ensure the order belongs to the customer
        if ($order->customer_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized access to this order',
            ], 403);
        }

        $designProofs = $this->designProofService->getDesignProofsForOrder($order->id);

        return response()->json([
            'message' => 'Design proofs retrieved successfully',
            'data' => $designProofs,
        ]);
    }

    /**
     * View a specific design proof
     */
    public function show(Request $request, DesignProof $designProof): JsonResponse
    {
        // Ensure the design proof belongs to the customer's order
        $orderItem = $designProof->orderItem()->with('order')->first();

        if ($orderItem->order->customer_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized access to this design proof',
            ], 403);
        }

        return response()->json([
            'message' => 'Design proof retrieved successfully',
            'data' => $designProof->load(['orderItem.product', 'uploadedBy', 'reviewedBy']),
        ]);
    }

    /**
     * Approve a design proof
     */
    public function approve(Request $request, DesignProof $designProof): JsonResponse
    {
        // Ensure the design proof belongs to the customer's order
        $orderItem = $designProof->orderItem()->with('order')->first();

        if ($orderItem->order->customer_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized access to this design proof',
            ], 403);
        }

        try {
            $updatedProof = $this->designProofService->approveDesignProof(
                $designProof,
                $request->user()
            );

            return response()->json([
                'message' => 'Design proof approved successfully',
                'data' => $updatedProof->load(['orderItem.product', 'uploadedBy', 'reviewedBy']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to approve design proof',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Request revision for a design proof
     */
    public function requestRevision(Request $request, DesignProof $designProof): JsonResponse
    {
        // Ensure the design proof belongs to the customer's order
        $orderItem = $designProof->orderItem()->with('order')->first();

        if ($orderItem->order->customer_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized access to this design proof',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'feedback' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $updatedProof = $this->designProofService->requestRevision(
                $designProof,
                $request->user(),
                $request->input('feedback')
            );

            return response()->json([
                'message' => 'Revision requested successfully',
                'data' => $updatedProof->load(['orderItem.product', 'uploadedBy', 'reviewedBy']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to request revision',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a design proof
     */
    public function reject(Request $request, DesignProof $designProof): JsonResponse
    {
        // Ensure the design proof belongs to the customer's order
        $orderItem = $designProof->orderItem()->with('order')->first();

        if ($orderItem->order->customer_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized access to this design proof',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $updatedProof = $this->designProofService->rejectDesignProof(
                $designProof,
                $request->user(),
                $request->input('reason')
            );

            return response()->json([
                'message' => 'Design proof rejected successfully',
                'data' => $updatedProof->load(['orderItem.product', 'uploadedBy', 'reviewedBy']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to reject design proof',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

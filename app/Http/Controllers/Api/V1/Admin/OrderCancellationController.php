<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderCancellationRequest;
use App\Services\OrderCancellationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for handling order cancellation requests (Admin only).
 */
class OrderCancellationController extends Controller
{
    public function __construct(
        protected OrderCancellationService $cancellationService
    ) {}

    /**
     * List all pending cancellation requests.
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status', 'pending');

        $cancellationRequests = OrderCancellationRequest::with([
            'order.customer',
            'order.items.product',
            'requestedBy',
            'reviewedBy',
        ])
            ->where('status', $status)
            ->latest()
            ->paginate(20);

        return response()->json($cancellationRequests);
    }

    /**
     * Show a specific cancellation request.
     */
    public function show(OrderCancellationRequest $cancellationRequest): JsonResponse
    {
        $cancellationRequest->load([
            'order.customer',
            'order.items.product',
            'order.items.variant',
            'order.payments',
            'requestedBy',
            'reviewedBy',
        ]);

        return response()->json([
            'data' => $cancellationRequest,
        ]);
    }

    /**
     * Approve a cancellation request.
     */
    public function approve(Request $request, OrderCancellationRequest $cancellationRequest): JsonResponse
    {
        $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->cancellationService->approveCancellation(
                $cancellationRequest,
                $request->user(),
                $request->input('notes')
            );

            return response()->json([
                'message' => 'Permintaan pembatalan telah disetujui.',
                'data' => $cancellationRequest->fresh([
                    'order',
                    'requestedBy',
                    'reviewedBy',
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menyetujui pembatalan: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a cancellation request.
     */
    public function reject(Request $request, OrderCancellationRequest $cancellationRequest): JsonResponse
    {
        $request->validate([
            'notes' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'notes.required' => 'Alasan penolakan harus diisi.',
            'notes.min' => 'Alasan penolakan minimal 10 karakter.',
        ]);

        try {
            $this->cancellationService->rejectCancellation(
                $cancellationRequest,
                $request->user(),
                $request->input('notes')
            );

            return response()->json([
                'message' => 'Permintaan pembatalan telah ditolak.',
                'data' => $cancellationRequest->fresh([
                    'order',
                    'requestedBy',
                    'reviewedBy',
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menolak pembatalan: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cancellation statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'pending' => OrderCancellationRequest::where('status', OrderCancellationRequest::STATUS_PENDING)->count(),
            'approved' => OrderCancellationRequest::where('status', OrderCancellationRequest::STATUS_APPROVED)->count(),
            'rejected' => OrderCancellationRequest::where('status', OrderCancellationRequest::STATUS_REJECTED)->count(),
            'total' => OrderCancellationRequest::count(),
        ];

        return response()->json(['data' => $stats]);
    }
}

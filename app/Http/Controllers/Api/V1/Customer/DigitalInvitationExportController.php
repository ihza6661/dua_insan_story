<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\DigitalInvitation;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DigitalInvitationExportController extends Controller
{
    public function __construct(
        private ExportService $exportService
    ) {}

    /**
     * Export invitation as PDF.
     *
     * @param Request $request
     * @param int $invitationId
     * @return \Illuminate\Http\Response|JsonResponse
     */
    public function exportPdf(Request $request, int $invitationId)
    {
        // Get invitation with ownership check
        $invitation = DigitalInvitation::with(['template', 'data'])
            ->where('id', $invitationId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$invitation) {
            return response()->json([
                'message' => 'Invitation not found or you do not have permission to access it',
            ], 404);
        }

        // Validate invitation can be exported
        try {
            $this->exportService->validateForExport($invitation);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Cannot export invitation: ' . $e->getMessage(),
            ], 400);
        }

        try {
            // Generate and download PDF
            // TODO: Support custom export options from request
            return $this->exportService->downloadPdf($invitation);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save PDF to storage and return URL.
     *
     * @param Request $request
     * @param int $invitationId
     * @return JsonResponse
     */
    public function savePdf(Request $request, int $invitationId): JsonResponse
    {
        // Get invitation with ownership check
        $invitation = DigitalInvitation::with(['template', 'data'])
            ->where('id', $invitationId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$invitation) {
            return response()->json([
                'message' => 'Invitation not found or you do not have permission to access it',
            ], 404);
        }

        // Validate invitation can be exported
        try {
            $this->exportService->validateForExport($invitation);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Cannot export invitation: ' . $e->getMessage(),
            ], 400);
        }

        try {
            // Save PDF to storage
            $path = $this->exportService->savePdf($invitation);
            $url = asset('storage/' . $path);

            return response()->json([
                'message' => 'PDF saved successfully',
                'data' => [
                    'path' => $path,
                    'url' => $url,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to save PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get export statistics for invitation.
     *
     * @param Request $request
     * @param int $invitationId
     * @return JsonResponse
     */
    public function getExportStats(Request $request, int $invitationId): JsonResponse
    {
        // Get invitation with ownership check
        $invitation = DigitalInvitation::with(['template', 'data'])
            ->where('id', $invitationId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$invitation) {
            return response()->json([
                'message' => 'Invitation not found or you do not have permission to access it',
            ], 404);
        }

        try {
            $stats = $this->exportService->getExportStats($invitation);

            return response()->json([
                'message' => 'Export stats retrieved successfully',
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get export stats: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview PDF metadata (without generating the full PDF).
     *
     * @param Request $request
     * @param int $invitationId
     * @return JsonResponse
     */
    public function previewPdfMetadata(Request $request, int $invitationId): JsonResponse
    {
        // Get invitation with ownership check
        $invitation = DigitalInvitation::with(['template', 'data'])
            ->where('id', $invitationId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$invitation) {
            return response()->json([
                'message' => 'Invitation not found or you do not have permission to access it',
            ], 404);
        }

        try {
            $metadata = $this->exportService->getPdfMetadata($invitation);

            return response()->json([
                'message' => 'PDF metadata retrieved successfully',
                'data' => $metadata,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get PDF metadata: ' . $e->getMessage(),
            ], 500);
        }
    }
}

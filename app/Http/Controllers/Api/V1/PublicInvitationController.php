<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DigitalInvitationService;
use Illuminate\Http\JsonResponse;

class PublicInvitationController extends Controller
{
    public function __construct(
        private DigitalInvitationService $invitationService
    ) {}

    /**
     * Display the public invitation by slug.
     * This endpoint is accessible without authentication for guests.
     */
    public function show(string $slug): JsonResponse
    {
        $invitationData = $this->invitationService->getPublicData($slug);

        if (! $invitationData) {
            return response()->json([
                'message' => 'Invitation not found or has expired',
            ], 404);
        }

        return response()->json([
            'message' => 'Invitation retrieved successfully',
            'data' => $invitationData,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Get all notifications for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 20);
        $notifications = $this->notificationService->getUserNotifications(
            $request->user()->id,
            $perPage
        );

        return response()->json([
            'message' => 'Notifications retrieved successfully',
            'data' => $notifications,
        ]);
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount($request->user()->id);

        return response()->json([
            'message' => 'Unread count retrieved successfully',
            'data' => [
                'unread_count' => $count,
            ],
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $success = $this->notificationService->markAsRead($id, $request->user()->id);

        if (!$success) {
            return response()->json([
                'message' => 'Notification not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user()->id);

        return response()->json([
            'message' => 'All notifications marked as read',
            'data' => [
                'updated_count' => $count,
            ],
        ]);
    }
}

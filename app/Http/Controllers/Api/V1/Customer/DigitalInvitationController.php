<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\DigitalInvitation;
use App\Models\Order;
use App\Services\DigitalInvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DigitalInvitationController extends Controller
{
    public function __construct(
        private DigitalInvitationService $invitationService
    ) {}

    /**
     * Display a listing of the authenticated user's digital invitations.
     */
    public function index(Request $request): JsonResponse
    {
        $invitations = $this->invitationService->getByUser($request->user()->id);

        return response()->json([
            'message' => 'Invitations retrieved successfully',
            'data' => $invitations->map(function ($invitation) {
                return [
                    'id' => $invitation->id,
                    'order_id' => $invitation->order_id,
                    'slug' => $invitation->slug,
                    'status' => $invitation->status,
                    'public_url' => $invitation->public_url,
                    'view_count' => $invitation->view_count,
                    'expires_at' => $invitation->expires_at?->toISOString(),
                    'is_expired' => $invitation->is_expired,
                    'template' => [
                        'id' => $invitation->template->id,
                        'name' => $invitation->template->name,
                        'slug' => $invitation->template->slug,
                        'thumbnail_image' => $invitation->template->thumbnail_image,
                    ],
                    'order' => [
                        'order_number' => $invitation->order->order_number,
                        'status' => $invitation->order->status,
                    ],
                    'customization_data' => $invitation->data ? [
                        'bride_name' => $invitation->data->bride_name,
                        'groom_name' => $invitation->data->groom_name,
                        'event_date' => $invitation->data->event_date,
                        'venue_name' => $invitation->data->venue_name,
                        'has_photos' => count($invitation->data->photo_paths ?? []) > 0,
                    ] : null,
                    'created_at' => $invitation->created_at->toISOString(),
                    'updated_at' => $invitation->updated_at->toISOString(),
                ];
            }),
        ]);
    }

    /**
     * Create digital invitation from a paid order containing digital products.
     */
    public function createFromOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify order belongs to authenticated user and is paid
        $order = Order::where('id', $request->order_id)
            ->where('customer_id', $request->user()->id)
            ->where('order_status', 'Paid')
            ->first();

        if (! $order) {
            return response()->json([
                'message' => 'Order not found or not paid yet',
            ], 404);
        }

        // Check if order has digital products
        $hasDigitalProduct = $order->items()
            ->whereHas('product', fn ($q) => $q->where('product_type', 'digital'))
            ->exists();

        if (! $hasDigitalProduct) {
            return response()->json([
                'message' => 'Order does not contain digital products',
            ], 400);
        }

        // Check if invitation already exists for this order
        if (DigitalInvitation::where('order_id', $order->id)->exists()) {
            return response()->json([
                'message' => 'Invitation already created for this order',
            ], 409);
        }

        // Create invitation
        $invitation = $this->invitationService->createFromOrder($order);

        if (! $invitation) {
            return response()->json([
                'message' => 'Failed to create invitation',
            ], 500);
        }

        return response()->json([
            'message' => 'Invitation created successfully',
            'data' => [
                'id' => $invitation->id,
                'slug' => $invitation->slug,
                'status' => $invitation->status,
                'template' => [
                    'id' => $invitation->template->id,
                    'name' => $invitation->template->name,
                ],
            ],
        ], 201);
    }

    /**
     * Display the specified digital invitation.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $invitation = DigitalInvitation::with(['template', 'order', 'data'])
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $invitation) {
            return response()->json([
                'message' => 'Invitation not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Invitation retrieved successfully',
            'data' => [
                'id' => $invitation->id,
                'order_id' => $invitation->order_id,
                'slug' => $invitation->slug,
                'status' => $invitation->status,
                'public_url' => $invitation->public_url,
                'view_count' => $invitation->view_count,
                'expires_at' => $invitation->expires_at?->toISOString(),
                'is_expired' => $invitation->is_expired,
                'template' => [
                    'id' => $invitation->template->id,
                    'name' => $invitation->template->name,
                    'slug' => $invitation->template->slug,
                    'description' => $invitation->template->description,
                    'thumbnail_image' => $invitation->template->thumbnail_image,
                    'price' => $invitation->template->price,
                    'template_component' => $invitation->template->template_component,
                ],
                'order' => [
                    'id' => $invitation->order->id,
                    'order_number' => $invitation->order->order_number,
                    'status' => $invitation->order->status,
                    'total_amount' => $invitation->order->total_amount,
                ],
                'customization_data' => $invitation->data ? [
                    'bride_name' => $invitation->data->bride_name,
                    'groom_name' => $invitation->data->groom_name,
                    'event_date' => $invitation->data->event_date,
                    'event_time' => $invitation->data->event_time,
                    'venue_name' => $invitation->data->venue_name,
                    'venue_address' => $invitation->data->venue_address,
                    'venue_map_url' => $invitation->data->venue_map_url,
                    'additional_info' => $invitation->data->additional_info,
                    'photo_urls' => $invitation->data->photo_urls,
                    'custom_fields' => $invitation->data->custom_fields,
                ] : null,
                'created_at' => $invitation->created_at->toISOString(),
                'updated_at' => $invitation->updated_at->toISOString(),
            ],
        ]);
    }

    /**
     * Update the invitation customization data.
     */
    public function updateCustomization(Request $request, int $id): JsonResponse
    {
        $invitation = DigitalInvitation::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $invitation) {
            return response()->json([
                'message' => 'Invitation not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'bride_name' => 'sometimes|string|max:255',
            'groom_name' => 'sometimes|string|max:255',
            'event_date' => 'sometimes|date',
            'event_time' => 'sometimes|string|max:50',
            'venue_name' => 'sometimes|string|max:255',
            'venue_address' => 'sometimes|string|max:500',
            'venue_map_url' => 'sometimes|url|max:500',
            'additional_info' => 'sometimes|string|max:2000',
            'custom_fields' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $this->invitationService->updateCustomization(
                $invitation->id,
                $validator->validated()
            );

            return response()->json([
                'message' => 'Invitation customization updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update customization',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload a photo for the invitation.
     */
    public function uploadPhoto(Request $request, int $id): JsonResponse
    {
        $invitation = DigitalInvitation::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $invitation) {
            return response()->json([
                'message' => 'Invitation not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $photoUrl = $this->invitationService->uploadPhoto(
                $invitation->id,
                $request->file('photo')
            );

            return response()->json([
                'message' => 'Photo uploaded successfully',
                'data' => [
                    'photo_url' => $photoUrl,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to upload photo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a photo from the invitation.
     */
    public function deletePhoto(Request $request, int $id, int $photoIndex): JsonResponse
    {
        $invitation = DigitalInvitation::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $invitation) {
            return response()->json([
                'message' => 'Invitation not found',
            ], 404);
        }

        try {
            $this->invitationService->deletePhoto($invitation->id, $photoIndex);

            return response()->json([
                'message' => 'Photo deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete photo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activate the invitation (make it publicly accessible).
     */
    public function activate(Request $request, int $id): JsonResponse
    {
        $invitation = DigitalInvitation::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $invitation) {
            return response()->json([
                'message' => 'Invitation not found',
            ], 404);
        }

        try {
            $invitation = $this->invitationService->activate($invitation->id);

            return response()->json([
                'message' => 'Invitation activated successfully',
                'data' => [
                    'public_url' => $invitation->public_url,
                    'status' => $invitation->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to activate invitation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deactivate the invitation (make it private again).
     */
    public function deactivate(Request $request, int $id): JsonResponse
    {
        $invitation = DigitalInvitation::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $invitation) {
            return response()->json([
                'message' => 'Invitation not found',
            ], 404);
        }

        try {
            $invitation = $this->invitationService->deactivate($invitation->id);

            return response()->json([
                'message' => 'Invitation deactivated successfully',
                'data' => [
                    'status' => $invitation->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to deactivate invitation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

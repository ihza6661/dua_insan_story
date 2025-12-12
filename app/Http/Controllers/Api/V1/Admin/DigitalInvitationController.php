<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\DigitalInvitation;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DigitalInvitationController extends Controller
{
    /**
     * Display a listing of digital invitations with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DigitalInvitation::with(['user:id,full_name,email', 'template:id,name', 'data'])
            ->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by template
        if ($request->filled('template_id')) {
            $query->where('template_id', $request->template_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by customer name or slug
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('slug', 'like', '%'.$search.'%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('full_name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
            });
        }

        $perPage = $request->input('per_page', 20);
        $invitations = $query->paginate($perPage);

        return response()->json([
            'data' => $invitations->map(function ($invitation) {
                return [
                    'id' => $invitation->id,
                    'user_id' => $invitation->user_id,
                    'user' => [
                        'id' => $invitation->user->id,
                        'full_name' => $invitation->user->full_name,
                        'email' => $invitation->user->email,
                    ],
                    'template_id' => $invitation->template_id,
                    'template' => [
                        'id' => $invitation->template->id,
                        'name' => $invitation->template->name,
                    ],
                    'slug' => $invitation->slug,
                    'status' => $invitation->status,
                    'activated_at' => $invitation->activated_at?->toISOString(),
                    'expires_at' => $invitation->expires_at?->toISOString(),
                    'scheduled_activation_at' => $invitation->scheduled_activation_at?->toISOString(),
                    'view_count' => $invitation->view_count,
                    'last_viewed_at' => $invitation->last_viewed_at?->toISOString(),
                    'created_at' => $invitation->created_at->toISOString(),
                    'updated_at' => $invitation->updated_at->toISOString(),
                    'public_url' => config('app.frontend_url').'/undangan/'.$invitation->slug,
                    'customization_data' => $invitation->data ? [
                        'template_name' => $invitation->data->template_name,
                        'couple_names' => $invitation->data->couple_names,
                        'bride_name' => $invitation->data->bride_name,
                        'groom_name' => $invitation->data->groom_name,
                    ] : null,
                ];
            }),
            'meta' => [
                'current_page' => $invitations->currentPage(),
                'from' => $invitations->firstItem(),
                'last_page' => $invitations->lastPage(),
                'per_page' => $invitations->perPage(),
                'to' => $invitations->lastItem(),
                'total' => $invitations->total(),
            ],
        ]);
    }

    /**
     * Display the specified invitation with full details.
     */
    public function show(int $id): JsonResponse
    {
        $invitation = DigitalInvitation::with(['user', 'template', 'data', 'order'])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $invitation->id,
                'user' => [
                    'id' => $invitation->user->id,
                    'full_name' => $invitation->user->full_name,
                    'email' => $invitation->user->email,
                    'phone_number' => $invitation->user->phone_number,
                ],
                'template' => [
                    'id' => $invitation->template->id,
                    'name' => $invitation->template->name,
                    'slug' => $invitation->template->slug,
                    'price' => $invitation->template->price,
                ],
                'order_id' => $invitation->order_id,
                'slug' => $invitation->slug,
                'status' => $invitation->status,
                'activated_at' => $invitation->activated_at?->toISOString(),
                'expires_at' => $invitation->expires_at?->toISOString(),
                'scheduled_activation_at' => $invitation->scheduled_activation_at?->toISOString(),
                'view_count' => $invitation->view_count,
                'last_viewed_at' => $invitation->last_viewed_at?->toISOString(),
                'created_at' => $invitation->created_at->toISOString(),
                'updated_at' => $invitation->updated_at->toISOString(),
                'public_url' => config('app.frontend_url').'/undangan/'.$invitation->slug,
                'customization_data' => $invitation->data ? [
                    'template_name' => $invitation->data->template_name,
                    'couple_names' => $invitation->data->couple_names,
                    'bride_name' => $invitation->data->bride_name,
                    'groom_name' => $invitation->data->groom_name,
                    'bride_parents' => $invitation->data->bride_parents,
                    'groom_parents' => $invitation->data->groom_parents,
                    'event_date' => $invitation->data->event_date,
                    'event_time' => $invitation->data->event_time,
                    'event_location' => $invitation->data->event_location,
                    'reception_date' => $invitation->data->reception_date,
                    'reception_time' => $invitation->data->reception_time,
                    'reception_location' => $invitation->data->reception_location,
                    'venue_maps_url' => $invitation->data->venue_maps_url,
                    'additional_info' => $invitation->data->additional_info,
                    'photos' => $invitation->data->photos,
                ] : null,
            ],
        ]);
    }

    /**
     * Get statistics for digital invitations.
     */
    public function statistics(): JsonResponse
    {
        $totalInvitations = DigitalInvitation::count();
        $activeInvitations = DigitalInvitation::where('status', DigitalInvitation::STATUS_ACTIVE)->count();
        $draftInvitations = DigitalInvitation::where('status', DigitalInvitation::STATUS_DRAFT)->count();
        $expiredInvitations = DigitalInvitation::where('status', DigitalInvitation::STATUS_EXPIRED)->count();
        $scheduledInvitations = DigitalInvitation::whereNotNull('scheduled_activation_at')
            ->where('status', DigitalInvitation::STATUS_DRAFT)
            ->count();
        $totalViews = DigitalInvitation::sum('view_count');

        // Calculate revenue from orders that include invitation templates
        // Assuming orders with digital_invitations relationship have template products
        $totalRevenue = Order::whereHas('digitalInvitations')
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        $templatesCount = DB::table('invitation_templates')->count();
        $activeTemplates = DB::table('invitation_templates')->where('is_active', true)->count();

        return response()->json([
            'data' => [
                'total_invitations' => $totalInvitations,
                'active_invitations' => $activeInvitations,
                'draft_invitations' => $draftInvitations,
                'expired_invitations' => $expiredInvitations,
                'scheduled_invitations' => $scheduledInvitations,
                'total_views' => $totalViews,
                'total_revenue' => $totalRevenue,
                'templates_count' => $templatesCount,
                'active_templates' => $activeTemplates,
            ],
        ]);
    }

    /**
     * Get all scheduled invitations (invitations with scheduled_activation_at set).
     */
    public function scheduled(Request $request): JsonResponse
    {
        $query = DigitalInvitation::with(['user:id,full_name,email', 'template:id,name', 'data'])
            ->whereNotNull('scheduled_activation_at')
            ->where('status', DigitalInvitation::STATUS_DRAFT)
            ->orderBy('scheduled_activation_at', 'asc');

        // Search by customer name or slug
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('slug', 'like', '%'.$search.'%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('full_name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
            });
        }

        // Filter by upcoming/overdue
        if ($request->filled('timeframe')) {
            if ($request->timeframe === 'upcoming') {
                $query->where('scheduled_activation_at', '>', now());
            } elseif ($request->timeframe === 'overdue') {
                $query->where('scheduled_activation_at', '<=', now());
            }
        }

        $perPage = $request->input('per_page', 20);
        $invitations = $query->paginate($perPage);

        return response()->json([
            'data' => $invitations->map(function ($invitation) {
                $scheduledAt = $invitation->scheduled_activation_at;
                $now = now();
                $isOverdue = $scheduledAt->isPast();
                $timeUntilActivation = $isOverdue
                    ? 'Overdue by '.$scheduledAt->diffForHumans($now, true)
                    : 'In '.$scheduledAt->diffForHumans($now, true);

                return [
                    'id' => $invitation->id,
                    'user_id' => $invitation->user_id,
                    'user' => [
                        'id' => $invitation->user->id,
                        'full_name' => $invitation->user->full_name,
                        'email' => $invitation->user->email,
                    ],
                    'template_id' => $invitation->template_id,
                    'template' => [
                        'id' => $invitation->template->id,
                        'name' => $invitation->template->name,
                    ],
                    'slug' => $invitation->slug,
                    'status' => $invitation->status,
                    'scheduled_activation_at' => $scheduledAt->toISOString(),
                    'scheduled_activation_relative' => $timeUntilActivation,
                    'is_overdue' => $isOverdue,
                    'expires_at' => $invitation->expires_at?->toISOString(),
                    'created_at' => $invitation->created_at->toISOString(),
                    'public_url' => config('app.frontend_url').'/undangan/'.$invitation->slug,
                    'customization_data' => $invitation->data ? [
                        'bride_name' => $invitation->data->bride_name,
                        'groom_name' => $invitation->data->groom_name,
                    ] : null,
                ];
            }),
            'meta' => [
                'current_page' => $invitations->currentPage(),
                'from' => $invitations->firstItem(),
                'last_page' => $invitations->lastPage(),
                'per_page' => $invitations->perPage(),
                'to' => $invitations->lastItem(),
                'total' => $invitations->total(),
            ],
        ]);
    }

    /**
     * Remove the specified invitation (soft delete).
     */
    public function destroy(int $id): JsonResponse
    {
        $invitation = DigitalInvitation::findOrFail($id);

        // Mark as expired instead of deleting
        $invitation->update([
            'status' => DigitalInvitation::STATUS_EXPIRED,
        ]);

        return response()->json([
            'message' => 'Undangan digital berhasil dinonaktifkan.',
        ]);
    }
}

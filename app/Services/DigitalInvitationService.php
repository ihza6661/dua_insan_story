<?php

namespace App\Services;

use App\Models\DigitalInvitation;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Service for managing digital invitations.
 */
class DigitalInvitationService
{
    /**
     * Auto-create digital invitations from ALL digital products in order after payment.
     * Returns array of created invitations.
     *
     * @return array<DigitalInvitation>
     */
    public function createFromOrder(Order $order): array
    {
        $invitations = [];

        // Get ALL digital items from order (not just first)
        $digitalItems = $order->items()
            ->whereHas('product', fn ($q) => $q->where('product_type', 'digital'))
            ->with(['product.template'])
            ->get();

        if ($digitalItems->isEmpty()) {
            Log::info('No digital products found in order', ['order_id' => $order->id]);

            return [];
        }

        // Create invitation for each digital product
        foreach ($digitalItems as $item) {
            if (! $item->product->template_id) {
                Log::warning('Digital product has no template_id', [
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                ]);

                continue;
            }

            try {
                $invitation = $this->createFromOrderItem($order, $item);
                if ($invitation) {
                    $invitations[] = $invitation;
                }
            } catch (\Exception $e) {
                Log::error('Failed to create invitation for order item', [
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $invitations;
    }

    /**
     * Create digital invitation from a specific order item.
     */
    protected function createFromOrderItem(Order $order, OrderItem $item): ?DigitalInvitation
    {
        return DB::transaction(function () use ($order, $item) {
            // Check if invitation already exists for this order and template (idempotency)
            $existingInvitation = DigitalInvitation::where('order_id', $order->id)
                ->where('template_id', $item->product->template_id)
                ->first();

            if ($existingInvitation) {
                Log::info('Invitation already exists for this order and template', [
                    'order_id' => $order->id,
                    'template_id' => $item->product->template_id,
                    'invitation_id' => $existingInvitation->id,
                ]);

                return $existingInvitation;
            }

            // Create invitation
            $invitation = DigitalInvitation::create([
                'user_id' => $order->customer_id,
                'order_id' => $order->id,
                'template_id' => $item->product->template_id,
                'slug' => $this->generateUniqueSlug(),
                'status' => DigitalInvitation::STATUS_DRAFT,
            ]);

            // Create invitation data with wedding data from order
            $this->populateInvitationData($invitation, $order);

            // Increment template usage count
            $invitation->template->incrementUsageCount();

            Log::info('Digital invitation created from order item', [
                'order_id' => $order->id,
                'invitation_id' => $invitation->id,
                'template_id' => $item->product->template_id,
                'slug' => $invitation->slug,
            ]);

            return $invitation->load('template', 'data');
        });
    }

    /**
     * Populate invitation data with wedding information from order.
     */
    protected function populateInvitationData(DigitalInvitation $invitation, Order $order): void
    {
        $invitationDetail = $order->invitationDetail;

        if (! $invitationDetail) {
            // Create empty data if no wedding details
            $invitation->data()->create([
                'digital_invitation_id' => $invitation->id,
            ]);
            Log::warning('No invitation details found in order', ['order_id' => $order->id]);

            return;
        }

        // Map wedding data from order to invitation data structure
        $customFields = [
            'bride_name' => $invitationDetail->bride_full_name,
            'bride_full_name' => $invitationDetail->bride_full_name,
            'bride_nickname' => $invitationDetail->bride_nickname,
            'bride_parents' => $invitationDetail->bride_parents,
            'groom_name' => $invitationDetail->groom_full_name,
            'groom_full_name' => $invitationDetail->groom_full_name,
            'groom_nickname' => $invitationDetail->groom_nickname,
            'groom_parents' => $invitationDetail->groom_parents,
            'akad_date' => $invitationDetail->akad_date?->format('Y-m-d'),
            'akad_time' => $invitationDetail->akad_time,
            'akad_location' => $invitationDetail->akad_location,
            'reception_date' => $invitationDetail->reception_date?->format('Y-m-d'),
            'reception_time' => $invitationDetail->reception_time,
            'reception_location' => $invitationDetail->reception_location,
            'gmaps_link' => $invitationDetail->gmaps_link,
            'prewedding_photo_path' => $invitationDetail->prewedding_photo_path,
        ];

        // Remove null values
        $customFields = array_filter($customFields, fn ($value) => $value !== null);

        // Create invitation data with populated fields
        $invitation->data()->create([
            'digital_invitation_id' => $invitation->id,
            'bride_name' => $invitationDetail->bride_full_name,
            'groom_name' => $invitationDetail->groom_full_name,
            'customization_json' => [
                'custom_fields' => $customFields,
            ],
        ]);

        Log::info('Invitation data populated from order', [
            'invitation_id' => $invitation->id,
            'fields_count' => count($customFields),
        ]);
    }

    /**
     * Generate unique slug for invitation.
     */
    protected function generateUniqueSlug(): string
    {
        do {
            $slug = 'inv-'.Str::random(10);
        } while (DigitalInvitation::where('slug', $slug)->exists());

        return strtolower($slug);
    }

    /**
     * Get all invitations for a user.
     */
    public function getByUser(int $userId, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = DigitalInvitation::with(['template', 'data', 'order'])
            ->where('user_id', $userId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get invitation by ID (with ownership check).
     */
    public function getByIdForUser(int $invitationId, int $userId): ?DigitalInvitation
    {
        return DigitalInvitation::with(['template', 'data'])
            ->where('id', $invitationId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Get invitation by slug (public access).
     */
    public function getBySlug(string $slug): ?DigitalInvitation
    {
        return DigitalInvitation::with(['template', 'data'])
            ->where('slug', $slug)
            ->where('status', DigitalInvitation::STATUS_ACTIVE)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Update invitation customization data.
     */
    public function updateCustomization(int $invitationId, array $data): bool
    {
        $invitation = DigitalInvitation::findOrFail($invitationId);

        $invitation->data()->updateOrCreate(
            ['digital_invitation_id' => $invitationId],
            array_filter($data) // Remove null values
        );

        return true;
    }

    /**
     * Upload photo for invitation.
     */
    public function uploadPhoto(int $invitationId, UploadedFile $file, ?string $photoType = null): string
    {
        $invitation = DigitalInvitation::findOrFail($invitationId);

        // Store photo in invitations/{id}/ directory (fixed interpolation)
        $path = $file->store("invitations/{$invitationId}", 'public');

        // Add to data JSON array with metadata
        $data = $invitation->data;
        $data->addPhoto($path, $photoType);

        // Return full URL
        return asset('storage/'.$path);
    }

    /**
     * Delete photo by index.
     */
    public function deletePhoto(int $invitationId, int $photoIndex): bool
    {
        $invitation = DigitalInvitation::findOrFail($invitationId);
        $data = $invitation->data;

        $photos = $data->photo_paths ?? [];
        if (! isset($photos[$photoIndex])) {
            return false;
        }

        // Delete file from storage
        Storage::disk('public')->delete($photos[$photoIndex]);

        // Remove from JSON array
        $data->removePhoto($photoIndex);

        return true;
    }

    /**
     * Activate invitation (make it publicly accessible).
     */
    public function activate(int $invitationId): DigitalInvitation
    {
        $invitation = DigitalInvitation::findOrFail($invitationId);

        $invitation->update([
            'status' => DigitalInvitation::STATUS_ACTIVE,
            'activated_at' => now(),
            'expires_at' => now()->addMonths(12), // MVP: Fixed 12 months
        ]);

        return $invitation->fresh(['template', 'data']);
    }

    /**
     * Schedule activation of invitation for a future date/time.
     */
    public function scheduleActivation(int $invitationId, string $scheduledAt): DigitalInvitation
    {
        $invitation = DigitalInvitation::findOrFail($invitationId);

        $scheduledDate = \Carbon\Carbon::parse($scheduledAt);

        // Validate that scheduled date is in the future
        if ($scheduledDate->isPast()) {
            throw new \InvalidArgumentException('Scheduled activation date must be in the future');
        }

        $invitation->update([
            'scheduled_activation_at' => $scheduledDate,
            // Keep status as draft until scheduled time
            'status' => DigitalInvitation::STATUS_DRAFT,
        ]);

        return $invitation->fresh(['template', 'data']);
    }

    /**
     * Cancel scheduled activation.
     */
    public function cancelScheduledActivation(int $invitationId): DigitalInvitation
    {
        $invitation = DigitalInvitation::findOrFail($invitationId);

        $invitation->update([
            'scheduled_activation_at' => null,
        ]);

        return $invitation->fresh(['template', 'data']);
    }

    /**
     * Activate invitations that are scheduled for activation.
     * This method is called by a scheduled job.
     */
    public function activateScheduledInvitations(): int
    {
        $invitations = DigitalInvitation::where('status', DigitalInvitation::STATUS_DRAFT)
            ->whereNotNull('scheduled_activation_at')
            ->where('scheduled_activation_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($invitations as $invitation) {
            $invitation->update([
                'status' => DigitalInvitation::STATUS_ACTIVE,
                'activated_at' => now(),
                'expires_at' => now()->addMonths(12),
                'scheduled_activation_at' => null, // Clear after activation
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Deactivate invitation.
     */
    public function deactivate(int $invitationId): DigitalInvitation
    {
        $invitation = DigitalInvitation::findOrFail($invitationId);

        $invitation->update([
            'status' => DigitalInvitation::STATUS_EXPIRED,
            'scheduled_activation_at' => null, // Clear any scheduled activation
        ]);

        return $invitation->fresh(['template', 'data']);
    }

    /**
     * Get preview data for invitation (works even if not active).
     * Used for "Preview Before Activation" feature.
     */
    public function getPreviewData(int $invitationId, int $userId): ?array
    {
        $invitation = $this->getByIdForUser($invitationId, $userId);

        if (! $invitation) {
            return null;
        }

        // Resolve color theme
        $colorTheme = $this->resolveColorTheme($invitation);

        return [
            'template' => [
                'name' => $invitation->template->name,
                'template_component' => $invitation->template->template_component,
            ],
            'customization' => $invitation->data,
            'view_count' => $invitation->view_count,
            'slug' => $invitation->slug,
            'colorTheme' => $colorTheme,
            'is_preview' => true, // Flag to show preview watermark
        ];
    }

    /**
     * Get public invitation data (for guest viewing).
     */
    public function getPublicData(string $slug): ?array
    {
        $invitation = $this->getBySlug($slug);

        if (! $invitation) {
            return null;
        }

        // Increment view count (throttled to prevent spam)
        $this->incrementViewCount($invitation);

        // Resolve color theme
        $colorTheme = $this->resolveColorTheme($invitation);

        return [
            'template' => [
                'name' => $invitation->template->name,
                'template_component' => $invitation->template->template_component,
            ],
            'customization' => $invitation->data,
            'view_count' => $invitation->view_count,
            'slug' => $invitation->slug,
            'colorTheme' => $colorTheme,
        ];
    }

    /**
     * Resolve the color theme for an invitation.
     * Returns the theme colors based on the selected color_scheme.
     */
    protected function resolveColorTheme(DigitalInvitation $invitation): ?array
    {
        $data = $invitation->data;
        $template = $invitation->template;

        // Get the selected theme key (default to 'default' if not set)
        $themeKey = $data->color_scheme ?? 'default';

        // Get available themes from template
        $availableThemes = $template->color_themes ?? [];

        // If theme doesn't exist, fall back to 'default'
        if (! isset($availableThemes[$themeKey]) && isset($availableThemes['default'])) {
            $themeKey = 'default';
        }

        // Return null if no themes available
        if (! isset($availableThemes[$themeKey])) {
            return null;
        }

        $theme = $availableThemes[$themeKey];

        return [
            'key' => $themeKey,
            'name' => $theme['name'] ?? 'Default',
            'colors' => [
                'primary' => $theme['primary'] ?? '#b89968',
                'secondary' => $theme['secondary'] ?? '#3d2e28',
                'accent' => $theme['accent'] ?? '#796656',
                'background' => $theme['background'] ?? '#faf9f8',
                'text' => $theme['text'] ?? '#3d2e28',
                'textMuted' => $theme['textMuted'] ?? '#796656',
            ],
        ];
    }

    /**
     * Increment view count (with basic throttling).
     */
    protected function incrementViewCount(DigitalInvitation $invitation): void
    {
        // Only increment if last view was more than 1 minute ago (prevent spam)
        if (! $invitation->last_viewed_at || $invitation->last_viewed_at->lt(now()->subMinute())) {
            $invitation->incrementViewCount();
        }
    }

    /**
     * Check if slug is available.
     */
    public function isSlugAvailable(string $slug): bool
    {
        return ! DigitalInvitation::where('slug', $slug)->exists();
    }

    /**
     * Update invitation slug (for premium features later).
     */
    public function updateSlug(int $invitationId, string $newSlug): DigitalInvitation
    {
        $invitation = DigitalInvitation::findOrFail($invitationId);

        if (! $this->isSlugAvailable($newSlug)) {
            throw new \Exception('Slug already taken');
        }

        $invitation->update(['slug' => $newSlug]);

        return $invitation->fresh(['template', 'data']);
    }

    /**
     * Check and expire invitations past their expiry date (for cron job).
     */
    public function expireOldInvitations(): int
    {
        return DigitalInvitation::where('status', DigitalInvitation::STATUS_ACTIVE)
            ->where('expires_at', '<', now())
            ->update(['status' => DigitalInvitation::STATUS_EXPIRED]);
    }
}

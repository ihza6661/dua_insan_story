<?php

namespace App\Services;

use App\Models\DigitalInvitation;
use App\Models\Order;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Service for managing digital invitations.
 */
class DigitalInvitationService
{
    /**
     * Auto-create digital invitation from order after payment.
     */
    public function createFromOrder(Order $order): ?DigitalInvitation
    {
        // Check if order has digital products
        $digitalItem = $order->items()
            ->whereHas('product', fn ($q) => $q->where('product_type', 'digital'))
            ->first();

        if (! $digitalItem || ! $digitalItem->product->template_id) {
            return null;
        }

        return DB::transaction(function () use ($order, $digitalItem) {
            // Create invitation
            $invitation = DigitalInvitation::create([
                'user_id' => $order->customer_id,
                'order_id' => $order->id,
                'template_id' => $digitalItem->product->template_id,
                'slug' => $this->generateUniqueSlug(),
                'status' => DigitalInvitation::STATUS_DRAFT,
            ]);

            // Create empty data record
            $invitation->data()->create([
                'digital_invitation_id' => $invitation->id,
            ]);

            // Increment template usage count
            $invitation->template->incrementUsageCount();

            return $invitation->load('template', 'data');
        });
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
    public function uploadPhoto(int $invitationId, UploadedFile $file): string
    {
        $invitation = DigitalInvitation::findOrFail($invitationId);

        // Store photo in invitations/{id}/ directory
        $path = $file->store("invitations/{invitationId}", 'public');

        // Add to data JSON array
        $data = $invitation->data;
        $data->addPhoto($path);

        return $path;
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
     * Deactivate invitation.
     */
    public function deactivate(int $invitationId): DigitalInvitation
    {
        $invitation = DigitalInvitation::findOrFail($invitationId);

        $invitation->update([
            'status' => DigitalInvitation::STATUS_EXPIRED,
        ]);

        return $invitation->fresh(['template', 'data']);
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

        return [
            'template' => [
                'name' => $invitation->template->name,
                'template_component' => $invitation->template->template_component,
            ],
            'customization' => $invitation->data,
            'view_count' => $invitation->view_count,
            'slug' => $invitation->slug,
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

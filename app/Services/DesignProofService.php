<?php

namespace App\Services;

use App\Mail\DesignProofReviewed;
use App\Mail\DesignProofUploaded;
use App\Models\DesignProof;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class DesignProofService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Upload a new design proof
     */
    public function uploadDesignProof(
        OrderItem $orderItem,
        UploadedFile $file,
        User $uploadedBy,
        ?string $adminNotes = null
    ): DesignProof {
        // Get the next version number
        $version = $orderItem->designProofs()->max('version') + 1;

        // Store the file using dynamic disk
        $disk = config('filesystems.user_uploads');
        $path = Storage::disk($disk)->put('design-proofs', $file);

        // Generate thumbnail if it's an image (only for local storage)
        $thumbnailPath = null;
        if ($disk === 'public' && str_starts_with($file->getMimeType(), 'image/')) {
            $thumbnailPath = $this->generateThumbnail($path);
        }

        // Create the design proof record
        $designProof = DesignProof::create([
            'order_item_id' => $orderItem->id,
            'uploaded_by' => $uploadedBy->id,
            'version' => $version,
            'file_url' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'thumbnail_url' => $thumbnailPath,
            'status' => DesignProof::STATUS_PENDING,
            'admin_notes' => $adminNotes,
            'customer_notified' => false,
        ]);

        // Send email notification to customer
        $designProof->load(['orderItem.order.customer', 'orderItem.product', 'orderItem.variant']);
        Mail::to($designProof->orderItem->order->customer->email)
            ->queue(new DesignProofUploaded($designProof));

        // Mark as notified
        $this->markCustomerNotified($designProof);

        // Create notification
        if (isset($this->notificationService)) {
            $this->notificationService->notifyDesignProof(
                userId: $designProof->orderItem->order->customer_id,
                designProofId: $designProof->id,
                action: 'uploaded',
                orderNumber: $designProof->orderItem->order->order_number,
                orderItemId: $designProof->order_item_id
            );
        }

        return $designProof;
    }

    /**
     * Generate a thumbnail for an image
     */
    protected function generateThumbnail(string $path): string
    {
        $fullPath = Storage::disk('public')->path($path);
        $thumbnailPath = 'design-proofs/thumbnails/'.basename($path);
        $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);

        // Create thumbnails directory if it doesn't exist
        $thumbnailDir = dirname($thumbnailFullPath);
        if (! file_exists($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        // Create thumbnail using basic PHP GD
        $this->createThumbnailWithGD($fullPath, $thumbnailFullPath, 300, 300);

        return $thumbnailPath;
    }

    /**
     * Create thumbnail using GD library
     */
    protected function createThumbnailWithGD(string $source, string $destination, int $width, int $height): void
    {
        $imageInfo = getimagesize($source);
        $mime = $imageInfo['mime'];

        // Create image resource based on type
        switch ($mime) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($source);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($source);
                break;
            default:
                return;
        }

        if (! $sourceImage) {
            return;
        }

        // Get original dimensions
        $origWidth = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);

        // Calculate aspect ratio
        $ratio = min($width / $origWidth, $height / $origHeight);
        $newWidth = (int) ($origWidth * $ratio);
        $newHeight = (int) ($origHeight * $ratio);

        // Create new image
        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and GIF
        if ($mime === 'image/png' || $mime === 'image/gif') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }

        // Resize
        imagecopyresampled(
            $thumbnail,
            $sourceImage,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $origWidth,
            $origHeight
        );

        // Save thumbnail
        switch ($mime) {
            case 'image/jpeg':
                imagejpeg($thumbnail, $destination, 85);
                break;
            case 'image/png':
                imagepng($thumbnail, $destination, 8);
                break;
            case 'image/gif':
                imagegif($thumbnail, $destination);
                break;
        }

        // Free memory
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);
    }

    /**
     * Approve a design proof
     */
    public function approveDesignProof(DesignProof $designProof, User $reviewedBy): DesignProof
    {
        $designProof->update([
            'status' => DesignProof::STATUS_APPROVED,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewedBy->id,
        ]);

        $designProof = $designProof->fresh();

        // Send email notification to customer
        $designProof->load(['orderItem.order.customer', 'orderItem.product', 'orderItem.variant']);
        Mail::to($designProof->orderItem->order->customer->email)
            ->queue(new DesignProofReviewed($designProof));

        return $designProof;
    }

    /**
     * Request revision for a design proof
     */
    public function requestRevision(
        DesignProof $designProof,
        User $reviewedBy,
        string $feedback
    ): DesignProof {
        $designProof->update([
            'status' => DesignProof::STATUS_REVISION_REQUESTED,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewedBy->id,
            'customer_feedback' => $feedback,
        ]);

        $designProof = $designProof->fresh();

        // Send email notification to admin/uploader
        $designProof->load(['orderItem.order.customer', 'orderItem.product', 'orderItem.variant', 'uploadedBy']);
        Mail::to($designProof->uploadedBy->email)
            ->queue(new DesignProofReviewed($designProof));

        return $designProof;
    }

    /**
     * Reject a design proof
     */
    public function rejectDesignProof(
        DesignProof $designProof,
        User $reviewedBy,
        string $reason
    ): DesignProof {
        $designProof->update([
            'status' => DesignProof::STATUS_REJECTED,
            'reviewed_at' => now(),
            'reviewed_by' => $reviewedBy->id,
            'customer_feedback' => $reason,
        ]);

        $designProof = $designProof->fresh();

        // Send email notification to admin/uploader
        $designProof->load(['orderItem.order.customer', 'orderItem.product', 'orderItem.variant', 'uploadedBy']);
        Mail::to($designProof->uploadedBy->email)
            ->queue(new DesignProofReviewed($designProof));

        return $designProof;
    }

    /**
     * Mark customer as notified
     */
    public function markCustomerNotified(DesignProof $designProof): void
    {
        $designProof->update([
            'customer_notified' => true,
            'customer_notified_at' => now(),
        ]);
    }

    /**
     * Get design proofs for an order
     */
    public function getDesignProofsForOrder(int $orderId): \Illuminate\Database\Eloquent\Collection
    {
        return DesignProof::forOrder($orderId)
            ->with(['orderItem.product', 'uploadedBy', 'reviewedBy'])
            ->orderBy('version', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get latest design proof for an order item
     */
    public function getLatestDesignProof(OrderItem $orderItem): ?DesignProof
    {
        return $orderItem->designProofs()
            ->orderBy('version', 'desc')
            ->first();
    }

    /**
     * Delete a design proof and its files
     */
    public function deleteDesignProof(DesignProof $designProof): bool
    {
        // Use dynamic disk for user uploads
        $disk = config('filesystems.user_uploads');

        // Delete files from storage
        if ($designProof->file_url) {
            Storage::disk($disk)->delete($designProof->file_url);
        }
        if ($designProof->thumbnail_url) {
            Storage::disk($disk)->delete($designProof->thumbnail_url);
        }

        return $designProof->delete();
    }
}

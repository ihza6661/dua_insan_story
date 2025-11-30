<?php

namespace App\Services;

use App\Models\DesignProof;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DesignProofService
{
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

        // Store the file
        $path = $file->store('design-proofs', 'public');

        // Generate thumbnail if it's an image
        $thumbnailPath = null;
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $thumbnailPath = $this->generateThumbnail($path);
        }

        // Create the design proof record
        return DesignProof::create([
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

        return $designProof->fresh();
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

        return $designProof->fresh();
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

        return $designProof->fresh();
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
        // Delete files from storage
        if ($designProof->file_url) {
            Storage::disk('public')->delete($designProof->file_url);
        }
        if ($designProof->thumbnail_url) {
            Storage::disk('public')->delete($designProof->thumbnail_url);
        }

        return $designProof->delete();
    }
}

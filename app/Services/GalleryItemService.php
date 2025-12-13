<?php

namespace App\Services;

use App\Models\GalleryItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class GalleryItemService
{
    public function createItem(array $data): GalleryItem
    {
        if (isset($data['file']) && $data['file'] instanceof UploadedFile) {
            $file = $data['file'];
            $disk = config('filesystems.user_uploads');
            $data['file_path'] = Storage::disk($disk)->put('gallery', $file);

            $mimeType = $file->getMimeType();
            if (str_starts_with($mimeType, 'image/')) {
                $data['media_type'] = 'image';
            } elseif (str_starts_with($mimeType, 'video/')) {
                $data['media_type'] = 'video';
            }
            unset($data['file']);
        }

        return GalleryItem::create($data);
    }

    public function updateItem(GalleryItem $galleryItem, array $data): GalleryItem
    {
        if (isset($data['file']) && $data['file'] instanceof UploadedFile) {
            $disk = config('filesystems.user_uploads');

            if ($galleryItem->file_path) {
                Storage::disk($disk)->delete($galleryItem->file_path);
            }

            $file = $data['file'];
            $data['file_path'] = Storage::disk($disk)->put('gallery', $file);

            $mimeType = $file->getMimeType();
            if (str_starts_with($mimeType, 'image/')) {
                $data['media_type'] = 'image';
            } elseif (str_starts_with($mimeType, 'video/')) {
                $data['media_type'] = 'video';
            }
            unset($data['file']);
        }

        $galleryItem->update($data);

        return $galleryItem;
    }

    public function deleteItem(GalleryItem $galleryItem): void
    {
        $galleryItem->delete();
    }
}

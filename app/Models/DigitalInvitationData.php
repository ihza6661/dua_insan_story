<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class DigitalInvitationData
 *
 * @property int $id
 * @property int $digital_invitation_id
 * @property string|null $bride_name
 * @property string|null $groom_name
 * @property \Carbon\Carbon|null $event_date
 * @property string|null $event_time
 * @property string|null $venue_name
 * @property string|null $venue_address
 * @property string|null $venue_maps_url
 * @property string|null $opening_message
 * @property array|null $photo_paths
 */
class DigitalInvitationData extends Model
{
    use HasFactory;

    protected $fillable = [
        'digital_invitation_id',
        'bride_name',
        'groom_name',
        'event_date',
        'event_time',
        'venue_name',
        'venue_address',
        'venue_maps_url',
        'opening_message',
        'photo_paths',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'photo_paths' => 'array',
        ];
    }

    // ========== RELATIONSHIPS ==========

    /**
     * Get the digital invitation that owns this data.
     */
    public function digitalInvitation(): BelongsTo
    {
        return $this->belongsTo(DigitalInvitation::class);
    }

    // ========== HELPER METHODS ==========

    /**
     * Add a photo path to the array.
     */
    public function addPhoto(string $path): void
    {
        $photos = $this->photo_paths ?? [];
        $photos[] = $path;
        $this->photo_paths = $photos;
        $this->save();
    }

    /**
     * Remove a photo by index.
     */
    public function removePhoto(int $index): void
    {
        $photos = $this->photo_paths ?? [];
        if (isset($photos[$index])) {
            unset($photos[$index]);
            $this->photo_paths = array_values($photos); // Re-index array
            $this->save();
        }
    }

    /**
     * Get the hero photo (first photo).
     */
    public function getHeroPhotoAttribute(): ?string
    {
        $photos = $this->photo_paths ?? [];

        return $photos[0] ?? null;
    }

    /**
     * Get gallery photos (all except first).
     */
    public function getGalleryPhotosAttribute(): array
    {
        $photos = $this->photo_paths ?? [];

        return array_slice($photos, 1);
    }
}

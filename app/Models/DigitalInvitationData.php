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
        'photo_metadata',
        'customization_json',
        'custom_fields', // Mutator will store this into customization_json
        'color_scheme',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = ['photo_urls'];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'photo_paths' => 'array',
            'photo_metadata' => 'array',
            'customization_json' => 'array',
        ];
    }

    /**
     * Get full URLs for photo paths.
     */
    public function getPhotoUrlsAttribute(): array
    {
        $photos = $this->photo_paths ?? [];
        $metadata = $this->photo_metadata ?? [];

        return array_map(function ($path, $index) use ($metadata) {
            // If path already starts with http, return as is
            $url = str_starts_with($path, 'http')
                ? $path
                : asset('storage/'.$path);

            // Return with metadata if available
            return [
                'url' => $url,
                'type' => $metadata[$index]['type'] ?? null,
            ];
        }, $photos, array_keys($photos));
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
     * Add a photo path to the array with optional metadata.
     */
    public function addPhoto(string $path, ?string $photoType = null): void
    {
        $photos = $this->photo_paths ?? [];
        $metadata = $this->photo_metadata ?? [];

        $photos[] = $path;
        $metadata[] = [
            'type' => $photoType,
            'uploaded_at' => now()->toISOString(),
        ];

        $this->photo_paths = $photos;
        $this->photo_metadata = $metadata;
        $this->save();
    }

    /**
     * Remove a photo by index along with its metadata.
     */
    public function removePhoto(int $index): void
    {
        $photos = $this->photo_paths ?? [];
        $metadata = $this->photo_metadata ?? [];

        if (isset($photos[$index])) {
            unset($photos[$index]);
            unset($metadata[$index]);

            $this->photo_paths = array_values($photos); // Re-index array
            $this->photo_metadata = array_values($metadata);
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

    /**
     * Get custom_fields from customization_json.
     * This is an accessor for backward compatibility.
     */
    public function getCustomFieldsAttribute(): ?array
    {
        $customizationJson = $this->customization_json;

        return $customizationJson['custom_fields'] ?? null;
    }

    /**
     * Set custom_fields into customization_json.
     * This is a mutator that stores into customization_json['custom_fields'].
     */
    public function setCustomFieldsAttribute($value): void
    {
        $customizationJson = $this->customization_json ?? [];
        $customizationJson['custom_fields'] = $value;
        $this->customization_json = $customizationJson;
    }
}

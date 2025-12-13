<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class InvitationDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'bride_full_name',
        'groom_full_name',
        'bride_nickname',
        'groom_nickname',
        'bride_parents',
        'groom_parents',
        'akad_date',
        'akad_time',
        'akad_location',
        'reception_date',
        'reception_time',
        'reception_location',
        'gmaps_link',
        'prewedding_photo_path',
    ];

    protected $casts = [
        'akad_date' => 'date',
        'reception_date' => 'date',
    ];

    protected $appends = ['prewedding_photo_url'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    protected function akadTime(): Attribute
    {
        return Attribute::make(
            set: function (string $value) {
                preg_match('/(\d{2}[\.:]\d{2})/u', $value, $matches);
                $time = $matches[1] ?? '00:00';

                return str_replace('.', ':', $time);
            },
        );
    }

    protected function receptionTime(): Attribute
    {
        return Attribute::make(
            set: function (string $value) {
                preg_match('/(\d{2}[\.:]\d{2})/u', $value, $matches);
                $time = $matches[1] ?? '00:00';

                return str_replace('.', ':', $time);
            },
        );
    }

    protected function preweddingPhotoUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->prewedding_photo_path) {
                    return null;
                }

                $disk = config('filesystems.user_uploads');
                
                // For Cloudinary, the path stored is a public_id
                // Generate the full URL using Cloudinary helper
                if ($disk === 'cloudinary') {
                    try {
                        return cloudinary()->image($this->prewedding_photo_path)->toUrl();
                    } catch (\Exception $e) {
                        Log::warning("Failed to generate Cloudinary URL for {$this->prewedding_photo_path}: {$e->getMessage()}");
                        return null;
                    }
                }
                
                // For local storage, use asset helper
                return asset('media/' . $this->prewedding_photo_path);
            }
        );
    }
}

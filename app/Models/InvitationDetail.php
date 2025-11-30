<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}

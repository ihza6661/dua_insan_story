<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'street',
        'city',
        'state',
        'subdistrict',
        'postal_code',
        'country',
    ];

    protected function fullAddress(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->street.', '.$this->city.', '.$this->state.' '.$this->postal_code.', '.$this->country,
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

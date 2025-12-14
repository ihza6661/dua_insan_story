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
        'label',
        'recipient_name',
        'recipient_phone',
        'street',
        'city',
        'state',
        'subdistrict',
        'postal_code',
        'country',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    protected function fullAddress(): Attribute
    {
        return Attribute::make(
            get: fn () => trim(implode(', ', array_filter([
                $this->street,
                $this->subdistrict,
                $this->city,
                $this->state.' '.$this->postal_code,
                $this->country,
            ]))),
        );
    }

    /**
     * Scope query to default addresses only
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope query to addresses for a specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Set this address as the default for the user
     */
    public function setAsDefault(): void
    {
        // Unset all other default addresses for this user
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        // Set this address as default
        $this->update(['is_default' => true]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

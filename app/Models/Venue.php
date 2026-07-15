<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Traits\UUID;

#[Fillable([
    'name',
    'slug',
    'address',
    'city',
    'latitude',
    'longitude',
    'featured_image',
    'bank_account',
    'commission_rate',
    'status'
])]

class Venue extends Model
{
    use HasFactory, UUID;

    /**
     * Get users (owners/staff) associated with the venue.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'venue_user')
                    ->withPivot('role');
    }

    /**
     * Get the operating hours for the venue.
     */
    public function operatingHours()
    {
        return $this->hasMany(VenueOperatingHour::class);
    }

    /**
     * Get the courts for the venue.
     */
    public function courts()
    {
        return $this->hasMany(Court::class);
    }
}

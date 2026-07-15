<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Traits\UUID;

#[Fillable([
    'venue_id',
    'day_of_week',
    'open_time',
    'close_time',
    'is_closed'
])]
class VenueOperatingHour extends Model
{
    use HasFactory, UUID;

    /**
     * Get the venue that owns the operating hour.
     */
    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }
}

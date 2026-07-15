<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Traits\UUID;

#[Fillable([
    'venue_id',
    'name',
    'category',
    'price_per_hour',
    'status'
])]
class Court extends Model
{
    use HasFactory, UUID;

    /**
     * Get the venue that owns the court.
     */
    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Get the images for the court.
     */
    public function images()
    {
        return $this->hasMany(CourtImage::class);
    }
}

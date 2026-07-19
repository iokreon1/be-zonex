<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'booking_code',
    'court_id',
    'venue_id',
    'user_id',
    'booking_date',
    'start_time',
    'end_time',
    'total_price',
    'status',
    'payment_status',
    'midtrans_order_id',
    'expires_at',
])]
class Booking extends Model
{
    use HasFactory, UUID;

    /**
     * Get the court that owns the booking.
     */
    public function court()
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * Get the venue that owns the booking.
     */
    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Get the user that owns the booking.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

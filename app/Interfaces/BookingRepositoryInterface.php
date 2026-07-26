<?php

namespace App\Interfaces;

use App\Models\Booking;

interface BookingRepositoryInterface
{
    /**
     * Create a new booking with validation and pessimistic locking.
     */
    public function create(array $data): Booking;
}

<?php

namespace App\Repositories;

use App\Interfaces\BookingRepositoryInterface;
use App\Models\Booking;
use App\Models\Court;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingRepository implements BookingRepositoryInterface
{
    /**
     * Create a new booking with validation and pessimistic locking.
     *
     * @throws ValidationException
     */
    public function create(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            $courtId = $data['court_id'];
            $bookingDate = $data['booking_date'];
            $startTime = $data['start_time']; // formatted as H:i
            $endTime = $data['end_time'];     // formatted as H:i
            $userId = auth()->id();

            // 1. Fetch Court and validate status
            $court = Court::with('venue.operatingHours')->findOrFail($courtId);
            if ($court->status !== 'active') {
                throw ValidationException::withMessages([
                    'court_id' => ['Lapangan tidak aktif.'],
                ]);
            }

            // 2. Validate booking time is not in the past
            $start = Carbon::parse($bookingDate.' '.$startTime);
            $end = Carbon::parse($bookingDate.' '.$endTime);

            if ($start->isPast()) {
                throw ValidationException::withMessages([
                    'start_time' => ['Waktu sewa tidak boleh di masa lalu.'],
                ]);
            }

            // 3. Ensure start/end time minutes and seconds are zero (whole hours)
            if ($start->minute !== 0 || $end->minute !== 0) {
                throw ValidationException::withMessages([
                    'start_time' => ['Waktu sewa harus dalam kelipatan jam utuh (menit harus 00).'],
                ]);
            }

            // Ensure duration is at least 1 hour and in whole hours
            $durationInHours = $start->diffInHours($end);
            $diffInMinutes = $start->diffInMinutes($end);
            if ($diffInMinutes % 60 !== 0 || $durationInHours < 1) {
                throw ValidationException::withMessages([
                    'end_time' => ['Durasi sewa harus minimal 1 jam dan dalam kelipatan jam utuh.'],
                ]);
            }

            // 4. Validate against venue operating hours
            $dayOfWeek = $start->dayOfWeek;
            $operatingHour = $court->venue->operatingHours->firstWhere('day_of_week', $dayOfWeek);
            if (! $operatingHour || $operatingHour->is_closed) {
                throw ValidationException::withMessages([
                    'booking_date' => ['Venue tutup pada hari yang dipilih.'],
                ]);
            }

            $openTime = Carbon::parse($bookingDate.' '.$operatingHour->open_time);
            $closeTime = Carbon::parse($bookingDate.' '.$operatingHour->close_time);
            if ($closeTime->lessThanOrEqualTo($openTime)) {
                $closeTime->addDay();
            }

            if ($start->lessThan($openTime) || $end->greaterThan($closeTime)) {
                throw ValidationException::withMessages([
                    'start_time' => ["Waktu sewa harus berada dalam jam operasional venue ({$operatingHour->open_time} - {$operatingHour->close_time})."],
                ]);
            }

            // 5. Pessimistic locking: Lock matching records on this date & court
            // This blocks other concurrent transactions for the same court and date.
            $activeBookings = Booking::where('court_id', $courtId)
                ->where('booking_date', $bookingDate)
                ->where(function ($query) {
                    $query->whereIn('payment_status', ['paid'])
                        ->orWhereIn('status', ['confirmed', 'completed'])
                        ->orWhere(function ($q) {
                            $q->where('status', 'pending')
                                ->where('payment_status', 'unpaid')
                                ->where('expires_at', '>', now());
                        });
                })
                ->lockForUpdate()
                ->get();

            // 6. Overlap validation
            $startStr = $start->format('H:i:s');
            $endStr = $end->format('H:i:s');

            foreach ($activeBookings as $existing) {
                // Formula: (start_time_new < end_time_old) AND (end_time_new > start_time_old)
                if ($startStr < $existing->end_time && $endStr > $existing->start_time) {
                    throw ValidationException::withMessages([
                        'booking_slot' => ['Jadwal lapangan tidak tersedia (sudah dipesan oleh pengguna lain).'],
                    ]);
                }
            }

            // 7. Calculate total price
            $totalPrice = $durationInHours * $court->price_per_hour;

            // 8. Generate booking code
            do {
                $bookingCode = 'ZX-'.Carbon::parse($bookingDate)->format('Ymd').'-'.strtoupper(Str::random(6));
            } while (Booking::where('booking_code', $bookingCode)->exists());

            // 9. Create booking
            return Booking::create([
                'booking_code' => $bookingCode,
                'venue_id' => $court->venue_id,
                'court_id' => $courtId,
                'user_id' => $userId,
                'booking_date' => $bookingDate,
                'start_time' => $startStr,
                'end_time' => $endStr,
                'total_price' => $totalPrice,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'expires_at' => now()->addMinutes(15),
            ]);
        });
    }
}

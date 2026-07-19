<?php

namespace App\Repositories;

use App\Interfaces\CourtRepositoryInterface;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtImage;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CourtRepository implements CourtRepositoryInterface
{
    /**
     * Get all courts for a venue.
     */
    public function allForVenue(string $venueId): Collection
    {
        return Court::with('images')->where('venue_id', $venueId)->get();
    }

    /**
     * Find a court by ID.
     */
    public function find(string $id): ?Court
    {
        return Court::with('images')->find($id);
    }

    /**
     * Create a new court.
     */
    public function create(array $data): Court
    {
        return Court::create($data);
    }

    /**
     * Update an existing court.
     */
    public function update(string $id, array $data): Court
    {
        $court = Court::findOrFail($id);
        $court->update($data);

        return $court;
    }

    /**
     * Delete a court and its images.
     */
    public function delete(string $id): bool
    {
        return DB::transaction(function () use ($id) {
            $court = Court::with('images')->findOrFail($id);
            foreach ($court->images as $image) {
                Storage::disk('public')->delete($image->image_path);
            }

            return $court->delete();
        });
    }

    /**
     * Upload an image for a court.
     *
     * @param  mixed  $file
     */
    public function uploadImage(string $courtId, $file, bool $isPrimary, string $folderName): CourtImage
    {
        return DB::transaction(function () use ($courtId, $file, $isPrimary, $folderName) {
            // Store image on public disk under the specified folderName
            $path = $file->store($folderName, 'public');

            // If this is primary, reset previous primary images for this court
            if ($isPrimary) {
                CourtImage::where('court_id', $courtId)->update(['is_primary' => false]);
            }

            return CourtImage::create([
                'court_id' => $courtId,
                'image_path' => $path,
                'is_primary' => $isPrimary,
            ]);
        });
    }

    /**
     * Delete a specific court image.
     */
    public function deleteImage(string $imageId): bool
    {
        return DB::transaction(function () use ($imageId) {
            $image = CourtImage::findOrFail($imageId);
            Storage::disk('public')->delete($image->image_path);

            return $image->delete();
        });
    }

    /**
     * Get court availability for a specific date.
     */
    public function getAvailability(string $courtId, string $date): array
    {
        $court = Court::with(['venue.operatingHours'])->findOrFail($courtId);
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        $operatingHour = $court->venue->operatingHours->firstWhere('day_of_week', $dayOfWeek);

        if (! $operatingHour || $operatingHour->is_closed) {
            return [
                'is_closed' => true,
                'open_time' => null,
                'close_time' => null,
                'slots' => [],
            ];
        }

        $startTime = Carbon::parse($date.' '.$operatingHour->open_time);
        $endTime = Carbon::parse($date.' '.$operatingHour->close_time);

        if ($endTime->lessThanOrEqualTo($startTime)) {
            $endTime->addDay();
        }

        // Fetch active bookings for this court and date
        $activeBookings = Booking::where('court_id', $courtId)
            ->where('booking_date', $date)
            ->where(function ($query) {
                $query->whereIn('payment_status', ['paid'])
                    ->orWhereIn('status', ['confirmed', 'completed'])
                    ->orWhere(function ($q) {
                        $q->where('status', 'pending')
                            ->where('payment_status', 'unpaid')
                            ->where('expires_at', '>', now());
                    });
            })
            ->get();

        $slots = [];
        $current = $startTime->copy();

        while ($current->lessThan($endTime)) {
            $slotStart = $current->copy();
            $slotEnd = $current->copy()->addHour();

            if ($slotEnd->greaterThan($endTime)) {
                break;
            }

            $startStr = $slotStart->format('H:i:s');
            $endStr = $slotEnd->format('H:i:s');

            $isBooked = $activeBookings->contains(function ($booking) use ($startStr, $endStr) {
                return $startStr < $booking->end_time && $endStr > $booking->start_time;
            });

            $slots[] = [
                'start_time' => $slotStart->format('H:i'),
                'end_time' => $slotEnd->format('H:i'),
                'is_booked' => $isBooked,
            ];

            $current->addHour();
        }

        return [
            'is_closed' => false,
            'open_time' => Carbon::parse($operatingHour->open_time)->format('H:i'),
            'close_time' => Carbon::parse($operatingHour->close_time)->format('H:i'),
            'slots' => $slots,
        ];
    }
}

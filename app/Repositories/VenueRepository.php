<?php

namespace App\Repositories;

use App\Models\Venue;
use App\Models\VenueOperatingHour;
use App\Interfaces\VenueRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class VenueRepository implements VenueRepositoryInterface
{
    /**
     * Get all venues associated with the user.
     *
     * @param string $userId
     * @return Collection
     */
    public function allForUser(string $userId): Collection
    {
        return Venue::whereHas('users', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->get();
    }

    /**
     * Find a venue by ID.
     *
     * @param string $id
     * @return Venue|null
     */
    public function find(string $id): ?Venue
    {
        return Venue::with('operatingHours')->find($id);
    }

    /**
     * Create a new venue.
     *
     * @param string $userId
     * @param array $data
     * @return Venue
     */
    public function create(string $userId, array $data): Venue
    {
        return DB::transaction(function () use ($userId, $data) {
            // Generate unique slug
            $slug = Str::slug($data['name']);
            $count = Venue::where('slug', 'like', $slug . '%')->count();
            if ($count > 0) {
                $slug .= '-' . ($count + 1);
            }
            $data['slug'] = $slug;

            $venue = Venue::create($data);

            // Link user to venue as owner
            $venue->users()->attach($userId, ['role' => 'owner']);

            // Initialize default operating hours (0 = Sunday ... 6 = Saturday)
            for ($day = 0; $day <= 6; $day++) {
                VenueOperatingHour::create([
                    'venue_id' => $venue->id,
                    'day_of_week' => $day,
                    'open_time' => '08:00:00',
                    'close_time' => '22:00:00',
                    'is_closed' => false,
                ]);
            }

            return $venue;
        });
    }

    /**
     * Update an existing venue.
     *
     * @param string $id
     * @param array $data
     * @return Venue
     */
    public function update(string $id, array $data): Venue
    {
        $venue = Venue::findOrFail($id);
        
        if (isset($data['name']) && $data['name'] !== $venue->name) {
            $slug = Str::slug($data['name']);
            $count = Venue::where('slug', 'like', $slug . '%')->where('id', '!=', $id)->count();
            if ($count > 0) {
                $slug .= '-' . ($count + 1);
            }
            $data['slug'] = $slug;
        }

        $venue->update($data);
        return $venue;
    }

    /**
     * Bulk update operating hours for a venue.
     *
     * @param string $venueId
     * @param array $hours
     * @return void
     */
    public function updateOperatingHours(string $venueId, array $hours): void
    {
        DB::transaction(function () use ($venueId, $hours) {
            foreach ($hours as $hour) {
                VenueOperatingHour::updateOrCreate(
                    [
                        'venue_id' => $venueId,
                        'day_of_week' => $hour['day_of_week']
                    ],
                    [
                        'open_time' => isset($hour['open_time']) ? \Carbon\Carbon::parse($hour['open_time'])->format('H:i:s') : null,
                        'close_time' => isset($hour['close_time']) ? \Carbon\Carbon::parse($hour['close_time'])->format('H:i:s') : null,
                        'is_closed' => $hour['is_closed'] ?? false
                    ]
                );
            }
        });
    }
}

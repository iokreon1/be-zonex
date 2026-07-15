<?php

namespace App\Interfaces;

use App\Models\Venue;
use Illuminate\Support\Collection;

interface VenueRepositoryInterface
{
    /**
     * Get all venues associated with the user.
     *
     * @param string $userId
     * @return Collection
     */
    public function allForUser(string $userId): Collection;

    /**
     * Find a venue by ID.
     *
     * @param string $id
     * @return Venue|null
     */
    public function find(string $id): ?Venue;

    /**
     * Create a new venue.
     *
     * @param string $userId
     * @param array $data
     * @return Venue
     */
    public function create(string $userId, array $data): Venue;

    /**
     * Update an existing venue.
     *
     * @param string $id
     * @param array $data
     * @return Venue
     */
    public function update(string $id, array $data): Venue;

    /**
     * Bulk update operating hours for a venue.
     *
     * @param string $venueId
     * @param array $hours
     * @return void
     */
    public function updateOperatingHours(string $venueId, array $hours): void;
}

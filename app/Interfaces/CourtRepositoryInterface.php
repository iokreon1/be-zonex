<?php

namespace App\Interfaces;

use App\Models\Court;
use App\Models\CourtImage;
use Illuminate\Support\Collection;

interface CourtRepositoryInterface
{
    /**
     * Get all courts for a venue.
     */
    public function allForVenue(string $venueId): Collection;

    /**
     * Find a court by ID.
     */
    public function find(string $id): ?Court;

    /**
     * Create a new court.
     */
    public function create(array $data): Court;

    /**
     * Update an existing court.
     */
    public function update(string $id, array $data): Court;

    /**
     * Delete a court and its images.
     */
    public function delete(string $id): bool;

    /**
     * Upload an image for a court.
     *
     * @param  mixed  $file
     */
    public function uploadImage(string $courtId, $file, bool $isPrimary, string $folderName): CourtImage;

    /**
     * Delete a specific court image.
     */
    public function deleteImage(string $imageId): bool;

    /**
     * Get court availability for a specific date.
     */
    public function getAvailability(string $courtId, string $date): array;
}

<?php

namespace App\Interfaces;

use App\Models\Court;
use App\Models\CourtImage;
use Illuminate\Support\Collection;

interface CourtRepositoryInterface
{
    /**
     * Get all courts for a venue.
     *
     * @param string $venueId
     * @return Collection
     */
    public function allForVenue(string $venueId): Collection;

    /**
     * Find a court by ID.
     *
     * @param string $id
     * @return Court|null
     */
    public function find(string $id): ?Court;

    /**
     * Create a new court.
     *
     * @param array $data
     * @return Court
     */
    public function create(array $data): Court;

    /**
     * Update an existing court.
     *
     * @param string $id
     * @param array $data
     * @return Court
     */
    public function update(string $id, array $data): Court;

    /**
     * Delete a court and its images.
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool;

    /**
     * Upload an image for a court.
     *
     * @param string $courtId
     * @param mixed $file
     * @param bool $isPrimary
     * @param string $folderName
     * @return CourtImage
     */
    public function uploadImage(string $courtId, $file, bool $isPrimary, string $folderName): CourtImage;

    /**
     * Delete a specific court image.
     *
     * @param string $imageId
     * @return bool
     */
    public function deleteImage(string $imageId): bool;
}

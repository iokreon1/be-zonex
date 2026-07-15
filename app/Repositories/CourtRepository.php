<?php

namespace App\Repositories;

use App\Models\Court;
use App\Models\CourtImage;
use App\Interfaces\CourtRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class CourtRepository implements CourtRepositoryInterface
{
    /**
     * Get all courts for a venue.
     *
     * @param string $venueId
     * @return Collection
     */
    public function allForVenue(string $venueId): Collection
    {
        return Court::with('images')->where('venue_id', $venueId)->get();
    }

    /**
     * Find a court by ID.
     *
     * @param string $id
     * @return Court|null
     */
    public function find(string $id): ?Court
    {
        return Court::with('images')->find($id);
    }

    /**
     * Create a new court.
     *
     * @param array $data
     * @return Court
     */
    public function create(array $data): Court
    {
        return Court::create($data);
    }

    /**
     * Update an existing court.
     *
     * @param string $id
     * @param array $data
     * @return Court
     */
    public function update(string $id, array $data): Court
    {
        $court = Court::findOrFail($id);
        $court->update($data);
        return $court;
    }

    /**
     * Delete a court and its images.
     *
     * @param string $id
     * @return bool
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
     * @param string $courtId
     * @param mixed $file
     * @param bool $isPrimary
     * @param string $folderName
     * @return CourtImage
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
                'is_primary' => $isPrimary
            ]);
        });
    }

    /**
     * Delete a specific court image.
     *
     * @param string $imageId
     * @return bool
     */
    public function deleteImage(string $imageId): bool
    {
        return DB::transaction(function () use ($imageId) {
            $image = CourtImage::findOrFail($imageId);
            Storage::disk('public')->delete($image->image_path);
            return $image->delete();
        });
    }
}

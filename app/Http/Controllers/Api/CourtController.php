<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\CourtStoreRequest;
use App\Http\Requests\CourtUpdateRequest;
use App\Http\Resources\CourtImageResource;
use App\Http\Resources\CourtResource;
use App\Interfaces\CourtRepositoryInterface;
use App\Interfaces\VenueRepositoryInterface;
use App\Models\CourtImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CourtController extends Controller
{
    protected CourtRepositoryInterface $courtRepository;

    protected VenueRepositoryInterface $venueRepository;

    /**
     * CourtController constructor.
     */
    public function __construct(CourtRepositoryInterface $courtRepository, VenueRepositoryInterface $venueRepository)
    {
        $this->courtRepository = $courtRepository;
        $this->venueRepository = $venueRepository;
    }

    /**
     * GET /api/venues/{venue_id}/courts
     */
    public function index($venueId)
    {
        $venue = $this->venueRepository->find($venueId);

        if (! $venue) {
            return ResponseHelper::jsonResponse(
                false,
                'Venue tidak ditemukan.',
                null,
                404
            );
        }

        Gate::authorize('view', $venue);

        $courts = $this->courtRepository->allForVenue($venueId);

        return ResponseHelper::jsonResponse(
            true,
            'Daftar lapangan berhasil diambil.',
            CourtResource::collection($courts),
            200
        );
    }

    /**
     * POST /api/courts
     */
    public function store(CourtStoreRequest $request)
    {
        $validated = $request->validated();

        $venue = $this->venueRepository->find($validated['venue_id']);
        if (! $venue) {
            return ResponseHelper::jsonResponse(
                false,
                'Venue tidak ditemukan.',
                null,
                404
            );
        }

        Gate::authorize('update', $venue);

        $court = $this->courtRepository->create($request->safe()->except(['images', 'primary_image_index']));

        if ($request->hasFile('images')) {
            $primaryIndex = $request->input('primary_image_index', 0);
            foreach ($request->file('images') as $index => $file) {
                $isPrimary = ($index == $primaryIndex);
                $this->courtRepository->uploadImage($court->id, $file, $isPrimary, 'courts');
            }
        }

        return ResponseHelper::jsonResponse(
            true,
            'Lapangan berhasil dibuat.',
            new CourtResource($court->load('images')),
            201
        );
    }

    /**
     * GET /api/courts/{id}
     */
    public function show($id)
    {
        $court = $this->courtRepository->find($id);

        if (! $court) {
            return ResponseHelper::jsonResponse(
                false,
                'Lapangan tidak ditemukan.',
                null,
                404
            );
        }

        Gate::authorize('view', $court);

        return ResponseHelper::jsonResponse(
            true,
            'Detail lapangan berhasil diambil.',
            new CourtResource($court),
            200
        );
    }

    /**
     * GET /api/courts/{id}/availability
     */
    public function availability(Request $request, $id)
    {
        $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $court = $this->courtRepository->find($id);

        if (! $court) {
            return ResponseHelper::jsonResponse(
                false,
                'Lapangan tidak ditemukan.',
                null,
                404
            );
        }

        $availability = $this->courtRepository->getAvailability($id, $request->query('date'));

        return ResponseHelper::jsonResponse(
            true,
            'Ketersediaan lapangan berhasil diambil.',
            $availability,
            200
        );
    }

    /**
     * PUT /api/courts/{id}
     */
    public function update(CourtUpdateRequest $request, $id)
    {
        $court = $this->courtRepository->find($id);

        if (! $court) {
            return ResponseHelper::jsonResponse(
                false,
                'Lapangan tidak ditemukan.',
                null,
                404
            );
        }

        Gate::authorize('update', $court);

        $updatedCourt = $this->courtRepository->update($id, $request->validated());

        return ResponseHelper::jsonResponse(
            true,
            'Lapangan berhasil diperbarui.',
            new CourtResource($updatedCourt->load('images')),
            200
        );
    }

    /**
     * DELETE /api/courts/{id}
     */
    public function destroy($id)
    {
        $court = $this->courtRepository->find($id);

        if (! $court) {
            return ResponseHelper::jsonResponse(
                false,
                'Lapangan tidak ditemukan.',
                null,
                404
            );
        }

        Gate::authorize('delete', $court);

        $this->courtRepository->delete($id);

        return ResponseHelper::jsonResponse(
            true,
            'Lapangan berhasil dihapus.',
            null,
            200
        );
    }

    /**
     * POST /api/courts/{id}/images
     */
    public function uploadImage(Request $request, $id)
    {
        $request->validate([
            'image' => ['required', 'image', 'max:2048'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $court = $this->courtRepository->find($id);

        if (! $court) {
            return ResponseHelper::jsonResponse(
                false,
                'Lapangan tidak ditemukan.',
                null,
                404
            );
        }

        Gate::authorize('update', $court);

        $image = $this->courtRepository->uploadImage(
            $id,
            $request->file('image'),
            $request->boolean('is_primary'),
            'courts'
        );

        return ResponseHelper::jsonResponse(
            true,
            'Foto lapangan berhasil diunggah.',
            new CourtImageResource($image),
            201
        );
    }

    /**
     * DELETE /api/courts/images/{image_id}
     */
    public function deleteImage($imageId)
    {
        $image = CourtImage::with('court.venue')->find($imageId);

        if (! $image) {
            return ResponseHelper::jsonResponse(
                false,
                'Foto lapangan tidak ditemukan.',
                null,
                404
            );
        }

        Gate::authorize('update', $image->court);

        $this->courtRepository->deleteImage($imageId);

        return ResponseHelper::jsonResponse(
            true,
            'Foto lapangan berhasil dihapus.',
            null,
            200
        );
    }
}

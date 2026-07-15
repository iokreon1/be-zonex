<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ResponseHelper;
use App\Interfaces\VenueRepositoryInterface;
use App\Http\Requests\VenueStoreRequest;
use App\Http\Requests\VenueUpdateRequest;
use App\Http\Requests\OperatingHoursUpdateRequest;
use App\Http\Resources\VenueResource;
use App\Http\Resources\VenueOperatingHourResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class VenueController extends Controller
{
    protected VenueRepositoryInterface $venueRepository;

    /**
     * VenueController constructor.
     *
     * @param VenueRepositoryInterface $venueRepository
     */
    public function __construct(VenueRepositoryInterface $venueRepository)
    {
        $this->venueRepository = $venueRepository;
    }

    /**
     * GET /api/venues
     */
    public function index()
    {
        $venues = $this->venueRepository->allForUser(auth()->id());

        return ResponseHelper::jsonResponse(
            true,
            'Daftar venue berhasil diambil.',
            VenueResource::collection($venues),
            200
        );
    }

    /**
     * POST /api/venues
     */
    public function store(VenueStoreRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('venues', 'public');
            $data['featured_image'] = $path;
        }

        $venue = $this->venueRepository->create(auth()->id(), $data);

        return ResponseHelper::jsonResponse(
            true,
            'Venue berhasil dibuat.',
            new VenueResource($venue->load('operatingHours')),
            201
        );
    }

    /**
     * GET /api/venues/{id}
     */
    public function show($id)
    {
        $venue = $this->venueRepository->find($id);

        if (!$venue) {
            return ResponseHelper::jsonResponse(
                false,
                'Venue tidak ditemukan.',
                null,
                404
            );
        }

        Gate::authorize('view', $venue);

        return ResponseHelper::jsonResponse(
            true,
            'Detail venue berhasil diambil.',
            new VenueResource($venue),
            200
        );
    }

    /**
     * PUT /api/venues/{id}
     */
    public function update(VenueUpdateRequest $request, $id)
    {
        $venue = $this->venueRepository->find($id);

        if (!$venue) {
            return ResponseHelper::jsonResponse(
                false,
                'Venue tidak ditemukan.',
                null,
                404
            );
        }

        Gate::authorize('update', $venue);

        $data = $request->validated();

        if ($request->hasFile('featured_image')) {
            if ($venue->featured_image) {
                Storage::disk('public')->delete($venue->featured_image);
            }
            $path = $request->file('featured_image')->store('venues', 'public');
            $data['featured_image'] = $path;
        }

        $updatedVenue = $this->venueRepository->update($id, $data);

        return ResponseHelper::jsonResponse(
            true,
            'Venue berhasil diperbarui.',
            new VenueResource($updatedVenue->load('operatingHours')),
            200
        );
    }

    /**
     * GET /api/venues/{id}/operating-hours
     */
    public function showOperatingHours($id)
    {
        $venue = $this->venueRepository->find($id);

        if (!$venue) {
            return ResponseHelper::jsonResponse(
                false,
                'Venue tidak ditemukan.',
                null,
                404
            );
        }

        Gate::authorize('view', $venue);

        return ResponseHelper::jsonResponse(
            true,
            'Jam operasional venue berhasil diambil.',
            VenueOperatingHourResource::collection($venue->operatingHours),
            200
        );
    }

    /**
     * PUT /api/venues/{id}/operating-hours
     */
    public function updateOperatingHours(OperatingHoursUpdateRequest $request, $id)
    {
        $venue = $this->venueRepository->find($id);

        if (!$venue) {
            return ResponseHelper::jsonResponse(
                false,
                'Venue tidak ditemukan.',
                null,
                404
            );
        }

        Gate::authorize('update', $venue);

        $this->venueRepository->updateOperatingHours($id, $request->validated()['hours']);

        return ResponseHelper::jsonResponse(
            true,
            'Jam operasional venue berhasil diperbarui.',
            new VenueResource($venue->refresh()->load('operatingHours')),
            200
        );
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookingStoreRequest;
use App\Http\Resources\BookingResource;
use App\Interfaces\BookingRepositoryInterface;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    protected BookingRepositoryInterface $bookingRepository;

    /**
     * BookingController constructor.
     */
    public function __construct(BookingRepositoryInterface $bookingRepository)
    {
        $this->bookingRepository = $bookingRepository;
    }

    /**
     * POST /api/bookings
     */
    public function store(BookingStoreRequest $request): JsonResponse
    {
        $booking = $this->bookingRepository->create($request->validated());

        return ResponseHelper::jsonResponse(
            true,
            'Pemesanan lapangan berhasil dibuat.',
            new BookingResource($booking->load(['court', 'venue'])),
            201
        );
    }
}

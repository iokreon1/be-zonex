<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourtController;
use App\Http\Controllers\Api\VenueController;
use Illuminate\Support\Facades\Route;

// Rute Publik (Tanpa Otentikasi)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/courts/{id}/availability', [CourtController::class, 'availability']);

// Rute Terproteksi (Memerlukan Token Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Rute Khusus dengan Peran venue_owner
    Route::middleware('role:venue_owner')->group(function () {
        // Venue CRUD
        Route::get('/venues', [VenueController::class, 'index']);
        Route::post('/venues', [VenueController::class, 'store']);
        Route::get('/venues/{id}', [VenueController::class, 'show']);
        Route::put('/venues/{id}', [VenueController::class, 'update']);
        Route::get('/venues/{id}/operating-hours', [VenueController::class, 'showOperatingHours']);
        Route::put('/venues/{id}/operating-hours', [VenueController::class, 'updateOperatingHours']);

        // Court CRUD
        Route::get('/venues/{venue_id}/courts', [CourtController::class, 'index']);
        Route::post('/courts', [CourtController::class, 'store']);
        Route::get('/courts/{id}', [CourtController::class, 'show']);
        Route::put('/courts/{id}', [CourtController::class, 'update']);
        Route::delete('/courts/{id}', [CourtController::class, 'destroy']);

        // Court Images
        Route::post('/courts/{id}/images', [CourtController::class, 'uploadImage']);
        Route::delete('/courts/images/{image_id}', [CourtController::class, 'deleteImage']);
    });
});

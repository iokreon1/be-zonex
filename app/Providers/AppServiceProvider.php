<?php

namespace App\Providers;

use App\Interfaces\AuthRepositoryInterface;
use App\Interfaces\BookingRepositoryInterface;
use App\Interfaces\CourtRepositoryInterface;
use App\Interfaces\VenueRepositoryInterface;
use App\Repositories\AuthRepository;
use App\Repositories\BookingRepository;
use App\Repositories\CourtRepository;
use App\Repositories\VenueRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            AuthRepositoryInterface::class,
            AuthRepository::class
        );
        $this->app->bind(
            VenueRepositoryInterface::class,
            VenueRepository::class
        );
        $this->app->bind(
            CourtRepositoryInterface::class,
            CourtRepository::class
        );
        $this->app->bind(
            BookingRepositoryInterface::class,
            BookingRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

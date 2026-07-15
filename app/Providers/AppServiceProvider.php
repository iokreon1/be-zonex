<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Interfaces\AuthRepositoryInterface::class,
            \App\Repositories\AuthRepository::class
        );
        $this->app->bind(
            \App\Interfaces\VenueRepositoryInterface::class,
            \App\Repositories\VenueRepository::class
        );
        $this->app->bind(
            \App\Interfaces\CourtRepositoryInterface::class,
            \App\Repositories\CourtRepository::class
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

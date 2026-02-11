<?php

namespace App\Modules\Spaces\Providers;

use Illuminate\Support\ServiceProvider;

final class SpacesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Modules\Spaces\Repositories\Interfaces\SpaceRepositoryInterface::class,
            \App\Modules\Spaces\Repositories\SpaceRepository::class
        );

        $this->app->bind(
            \App\Modules\Spaces\Services\Interfaces\SpaceServiceInterface::class,
            \App\Modules\Spaces\Services\SpaceService::class
        );
    }

    public function boot(): void
    {
    }
}

<?php

namespace App\Modules\SavedViews\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\SavedViews\Repositories\Interfaces\SavedViewRepositoryInterface;
use App\Modules\SavedViews\Repositories\SavedViewRepository;
use App\Modules\SavedViews\Services\Interfaces\SavedViewServiceInterface;
use App\Modules\SavedViews\Services\SavedViewService;

final class SavedViewsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SavedViewRepositoryInterface::class, SavedViewRepository::class);
        $this->app->bind(SavedViewServiceInterface::class, SavedViewService::class);
    }

    public function boot(): void
    {
    }
}

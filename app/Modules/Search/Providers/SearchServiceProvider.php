<?php

namespace App\Modules\Search\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Search\Services\Interfaces\SearchServiceInterface;
use App\Modules\Search\Services\SearchService;

final class SearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SearchServiceInterface::class, SearchService::class);
    }

    public function boot(): void {}
}

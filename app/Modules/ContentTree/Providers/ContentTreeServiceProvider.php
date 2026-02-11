<?php

namespace App\Modules\ContentTree\Providers;

use App\Modules\ContentTree\Observers\EntryObserver;
use App\Modules\ContentTree\Repositories\ContentTreeRepository;
use App\Modules\ContentTree\Repositories\Interfaces\ContentTreeRepositoryInterface;
use App\Modules\ContentTree\Services\ContentTreeService;
use App\Modules\ContentTree\Services\Interfaces\ContentTreeServiceInterface;
use Illuminate\Support\ServiceProvider;

final class ContentTreeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ContentTreeRepositoryInterface::class, ContentTreeRepository::class);
        $this->app->bind(ContentTreeServiceInterface::class, ContentTreeService::class);
    }

    public function boot(): void
    {
        \App\Models\Entry::observe(
            $this->app->make(EntryObserver::class)
        );
    }
}

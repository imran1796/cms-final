<?php

namespace App\Modules\Content\Providers;

use App\Models\Entry;
use App\Observers\EntrySearchObserver;
use App\Modules\Content\Listeners\EntryPublishedListener;
use App\Modules\Content\Events\EntryPublished;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

final class ContentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Modules\Content\Repositories\Interfaces\CollectionRepositoryInterface::class,
            \App\Modules\Content\Repositories\CollectionRepository::class
        );

        $this->app->bind(
            \App\Modules\Content\Services\ContentTypeServiceInterface::class,
            \App\Modules\Content\Services\ContentTypeService::class
        );

        $this->app->bind(
            \App\Modules\Content\Repositories\Interfaces\EntryRepositoryInterface::class,
            \App\Modules\Content\Repositories\EntryRepository::class
        );

        $this->app->bind(
            \App\Modules\Content\Services\EntryServiceInterface::class,
            \App\Modules\Content\Services\EntryService::class
        );

        $this->app->bind(
            \App\Modules\Content\Services\PublishingServiceInterface::class,
            \App\Modules\Content\Services\PublishingService::class
        );

        $this->app->bind(
            \App\Modules\Content\Services\Interfaces\EntryLockServiceInterface::class,
            \App\Modules\Content\Services\EntryLockService::class
        );

        $this->app->bind(
            \App\Modules\Content\Services\Interfaces\PreviewServiceInterface::class,
            \App\Modules\Content\Services\PreviewService::class
        );

        $this->app->singleton(\App\Modules\Content\Services\PreviewTokenService::class);

        $this->app->bind(
            \App\Modules\Content\Contracts\CacheInvalidatorInterface::class,
            \App\Modules\Content\Services\CacheInvalidator::class
        );

        $this->app->bind(
            \App\Modules\Content\Contracts\WebhookDispatcherInterface::class,
            \App\Modules\Content\Services\WebhookDispatcher::class
        );

        $this->app->bind(
            \App\Modules\Content\Revisions\Repositories\Interfaces\RevisionRepositoryInterface::class,
            \App\Modules\Content\Revisions\Repositories\RevisionRepository::class
        );

        $this->app->bind(
            \App\Modules\Content\Revisions\Services\Interfaces\RevisionServiceInterface::class,
            \App\Modules\Content\Revisions\Services\RevisionService::class
        );



    }

    public function boot(): void
    {
        Entry::observe(EntrySearchObserver::class);

        Event::listen(EntryPublished::class, EntryPublishedListener::class);
    }
}

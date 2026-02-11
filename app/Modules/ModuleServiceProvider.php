<?php

namespace App\Modules;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Modules\System\Providers\SystemServiceProvider;
use App\Modules\Spaces\Providers\SpacesServiceProvider;
use App\Modules\Content\Providers\ContentServiceProvider;
use App\Modules\Assets\Providers\AssetsServiceProvider;
use App\Modules\Finder\Providers\FinderServiceProvider;
use App\Modules\Search\Providers\SearchServiceProvider;
use App\Modules\SavedViews\Providers\SavedViewsServiceProvider;
use App\Modules\ContentDelivery\Providers\ContentDeliveryServiceProvider;
use App\Modules\Forms\Providers\FormsServiceProvider;
use App\Modules\ContentTree\Providers\ContentTreeServiceProvider;
use App\Modules\System\Realtime\Providers\RealtimeServiceProvider;
use App\Modules\System\Audit\Providers\AuditServiceProvider;
use App\Modules\System\Providers\SystemConsoleServiceProvider;

final class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $providers = [
            SystemServiceProvider::class,
            SpacesServiceProvider::class,
            ContentServiceProvider::class,
            AssetsServiceProvider::class,
            FinderServiceProvider::class,
            SearchServiceProvider::class,
            RealtimeServiceProvider::class,
            ContentTreeServiceProvider::class,
            SavedViewsServiceProvider::class,
            ContentDeliveryServiceProvider::class,
            FormsServiceProvider::class,
            AssetsServiceProvider::class,
            SearchServiceProvider::class,
            RealtimeServiceProvider::class,
            SystemConsoleServiceProvider::class,
            


        ];

        foreach ($providers as $provider) {
            $this->app->register($provider);
        }
    }

    public function boot(): void
    {
        Event::listen(\App\Modules\Content\Events\EntryPublished::class, \App\Listeners\SyncEntryToSearchIndex::class);

    }
}

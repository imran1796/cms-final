<?php

namespace App\Modules\ContentDelivery\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\ContentDelivery\Repositories\Interfaces\PublicContentRepositoryInterface;
use App\Modules\ContentDelivery\Repositories\PublicContentRepository;
use App\Modules\ContentDelivery\Services\Interfaces\PublicContentServiceInterface;
use App\Modules\ContentDelivery\Services\PublicContentService;
use App\Modules\ContentDelivery\Support\PublicPopulateService;

final class ContentDeliveryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PublicContentRepositoryInterface::class, PublicContentRepository::class);
        $this->app->bind(PublicContentServiceInterface::class, PublicContentService::class);

        $this->app->singleton(PublicPopulateService::class);
    }

    public function boot(): void
    {
    }
}

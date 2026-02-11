<?php

namespace App\Modules\Assets\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Assets\Repositories\Interfaces\MediaRepositoryInterface;
use App\Modules\Assets\Repositories\MediaRepository;
use App\Modules\Assets\Repositories\Interfaces\MediaVariantRepositoryInterface;
use App\Modules\Assets\Repositories\MediaVariantRepository;
use App\Modules\Assets\Repositories\Interfaces\MediaFolderRepositoryInterface;
use App\Modules\Assets\Repositories\MediaFolderRepository;

use App\Modules\Assets\Services\Interfaces\AssetServiceInterface;
use App\Modules\Assets\Services\AssetService;
use App\Modules\Assets\Services\Interfaces\AssetPublicServiceInterface;
use App\Modules\Assets\Services\AssetPublicService;
use App\Modules\Assets\Services\Interfaces\ImageTransformServiceInterface;
use App\Modules\Assets\Services\ImageTransformService;
use App\Modules\Assets\Services\ChunkedUploadService;
use App\Modules\Assets\Services\Interfaces\VideoMetadataServiceInterface;
use App\Modules\Assets\Services\VideoMetadataService;

final class AssetsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MediaRepositoryInterface::class, MediaRepository::class);
        $this->app->bind(MediaVariantRepositoryInterface::class, MediaVariantRepository::class);
        $this->app->bind(MediaFolderRepositoryInterface::class, MediaFolderRepository::class);

        $this->app->bind(AssetServiceInterface::class, AssetService::class);
        $this->app->singleton(ChunkedUploadService::class);
        $this->app->bind(AssetPublicServiceInterface::class, AssetPublicService::class);
        $this->app->bind(ImageTransformServiceInterface::class, ImageTransformService::class);
        $this->app->bind(VideoMetadataServiceInterface::class, VideoMetadataService::class);
    }

    public function boot(): void {}
}

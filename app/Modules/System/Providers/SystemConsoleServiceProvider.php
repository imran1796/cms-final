<?php

namespace App\Modules\System\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\System\Console\CmsHealthcheckCommand;
use App\Modules\System\Console\CmsCacheFlushCommand;
use App\Modules\System\Console\CmsModelCreateCommand;
use App\Modules\System\Console\CmsModelDeleteCommand;
use App\Modules\System\Console\CmsExportCommand;
use App\Modules\System\Console\CmsImportCommand;
use App\Modules\System\Console\CmsAssetsPresetsGenerateCommand;
use App\Modules\System\Console\CmsAssetsThumbhashGenerateCommand;
use App\Modules\System\Console\CmsAssetsCleanupCommand;

final class SystemConsoleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            CmsHealthcheckCommand::class,
            CmsCacheFlushCommand::class,
            CmsModelCreateCommand::class,
            CmsModelDeleteCommand::class,
            CmsExportCommand::class,
            CmsImportCommand::class,
            CmsAssetsPresetsGenerateCommand::class,
            CmsAssetsThumbhashGenerateCommand::class,
            CmsAssetsCleanupCommand::class,
        ]);
    }
}

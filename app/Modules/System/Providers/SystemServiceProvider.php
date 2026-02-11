<?php

namespace App\Modules\System\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class SystemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Modules\System\Auth\Services\Interfaces\AuthServiceInterface::class,
            \App\Modules\System\Auth\Services\AuthService::class
        );

        $this->app->bind(
            \App\Modules\System\System\Services\Interfaces\SystemServiceInterface::class,
            \App\Modules\System\System\Services\SystemService::class
        );
        
        $this->app->bind(
            \App\Modules\System\Audit\Repositories\Interfaces\AuditLogRepositoryInterface::class,
            \App\Modules\System\Audit\Repositories\AuditLogRepository::class
        );
        
        $this->app->bind(
            \App\Modules\System\Audit\Services\Interfaces\AuditLogServiceInterface::class,
            \App\Modules\System\Audit\Services\AuditLogService::class
        );

        $this->app->bind(
            \App\Modules\System\ApiKeys\Repositories\Interfaces\ApiKeyRepositoryInterface::class,
            \App\Modules\System\ApiKeys\Repositories\ApiKeyRepository::class
        );
        
        $this->app->bind(
            \App\Modules\System\ApiKeys\Services\Interfaces\ApiKeyServiceInterface::class,
            \App\Modules\System\ApiKeys\Services\ApiKeyService::class
        );
        
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\Route::middleware('api')
            ->prefix('api')
            ->group(app_path('Modules/System/Auth/Routes/api.php'));
    }
}

<?php

namespace App\Modules\System\Realtime\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\System\Realtime\Services\Interfaces\LockServiceInterface;
use App\Modules\System\Realtime\Services\LockService;

final class RealtimeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LockServiceInterface::class, LockService::class);
    }

    public function boot(): void {}
}

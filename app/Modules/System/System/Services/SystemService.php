<?php

namespace App\Modules\System\System\Services;

use App\Models\AuditLog;
use App\Models\Space;
use App\Models\User;
use App\Modules\System\Authorization\Services\AuthorizationService;
use App\Modules\System\System\Services\Interfaces\SystemServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

final class SystemService implements SystemServiceInterface
{
    public function __construct(
        private readonly AuthorizationService $authz
    ) {
    }

    public function health(): array
    {
        $db = $this->checkDb();
        $cache = $this->checkCache();
        $queue = $this->checkQueue();

        return [
            'db' => $db,
            'cache' => $cache,
            'queue' => $queue,
            'ok' => $db['ok'] && $cache['ok'] && $queue['ok'],
        ];
    }

    public function info(): array
    {
        $this->authz->requirePermission('manage_settings');

        return [
            'version' => config('system.version'),
            'build' => config('system.build'),
            'flags' => config('system.flags'),
            'app' => [
                'env' => config('app.env'),
                'debug' => (bool) config('app.debug'),
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
            ],
        ];
    }

    public function stats(): array
    {
        $this->authz->requirePermission('manage_settings');

        return [
            'users' => User::query()->count(),
            'spaces' => Space::query()->count(),
            'audit_logs' => AuditLog::query()->count(),
            'content' => [
                'types' => 0,
                'entries' => 0,
            ],
            'assets' => [
                'files' => 0,
            ],
        ];
    }

    private function checkDb(): array
    {
        try {
            DB::select('select 1');
            return ['ok' => true, 'message' => 'ok'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        $driver = config('cache.default', 'array');
        try {
            $key = 'health_check_' . Str::random(16);
            Cache::put($key, 1, 5);
            $v = Cache::get($key);
            Cache::forget($key);
            if ($v !== 1) {
                return ['ok' => false, 'error' => 'Cache get/set mismatch'];
            }
            return ['ok' => true, 'message' => "driver={$driver}"];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function checkQueue(): array
    {
        try {
            $driver = config('queue.default');
            if (!$driver) {
                return ['ok' => false, 'error' => 'queue.default not set'];
            }
            $conn = Queue::connection();
            if ($driver !== 'sync') {
                $conn->size('default');
            }
            return ['ok' => true, 'message' => "driver={$driver}"];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}

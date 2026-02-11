<?php

namespace App\Modules\System\Realtime\Services;

use Illuminate\Support\Facades\Cache;
use App\Modules\System\Realtime\Services\Interfaces\LockServiceInterface;

final class LockService implements LockServiceInterface
{
    private array $heldLocks = [];

    public function acquire(string $key, int $ttlSeconds): bool
    {
        $lock = Cache::lock($key, $ttlSeconds);
        if ($lock->get()) {
            $this->heldLocks[$key] = $lock;
            return true;
        }
        return false;
    }

    public function release(string $key): void
    {
        if (isset($this->heldLocks[$key])) {
            $this->heldLocks[$key]->release();
            unset($this->heldLocks[$key]);
        }
    }

    public function releaseByKey(string $key): void
    {
        Cache::lock($key)->forceRelease();
    }
}

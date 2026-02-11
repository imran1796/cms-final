<?php

namespace App\Modules\System\Realtime\Services\Interfaces;

interface LockServiceInterface
{
    public function acquire(string $key, int $ttlSeconds): bool;

    public function release(string $key): void;

    public function releaseByKey(string $key): void;
}

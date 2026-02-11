<?php

declare(strict_types=1);

namespace App\Modules\Content\Services\Interfaces;

use Illuminate\Http\Request;

interface EntryLockServiceInterface
{
    public function lock(string $collectionHandle, int $id, Request $request): array;

    public function unlock(string $collectionHandle, int $id): array;
}

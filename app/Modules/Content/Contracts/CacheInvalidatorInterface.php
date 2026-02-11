<?php

namespace App\Modules\Content\Contracts;

use App\Models\Entry;

interface CacheInvalidatorInterface
{
    public function invalidateEntry(int $spaceId, string $collectionHandle, Entry $entry): void;
}

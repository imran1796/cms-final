<?php

namespace App\Modules\Content\Contracts;

use App\Models\Entry;

interface WebhookDispatcherInterface
{
    public function dispatchEntryPublished(int $spaceId, string $collectionHandle, Entry $entry): void;
}

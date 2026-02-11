<?php

namespace App\Modules\ContentTree\Observers;

use App\Modules\ContentTree\Services\Interfaces\ContentTreeServiceInterface;

final class EntryObserver
{
    public function __construct(
        private readonly ContentTreeServiceInterface $tree,
    ) {}

    public function created($entry): void
    {
        $spaceId = (int) ($entry->space_id ?? 0);
        $collectionId = (int) $entry->collection_id;
        $entryId = (int) $entry->id;

        $collection = \App\Models\Collection::query()->find($collectionId);
        if (!$collection || $collection->type !== 'tree') {
            return;
        }

        $this->tree->ensureNodeForEntry($spaceId, $collectionId, $entryId);
    }
}

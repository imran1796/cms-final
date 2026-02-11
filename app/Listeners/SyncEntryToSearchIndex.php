<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;

final class SyncEntryToSearchIndex
{
    public function handle(object $event): void
    {
        $entry = $event->entry ?? null;

        if (!$entry) {
            Log::warning('SyncEntryToSearchIndex: missing entry on event');
            return;
        }

        if (($entry->status ?? null) !== 'published') return;

        if (!method_exists($entry, 'searchable')) {
            Log::info('Scout not enabled on entry model; skipping index sync');
            return;
        }

        try {
            $entry->searchable();
            Log::info('Entry synced to search index', ['entry_id' => $entry->id]);
        } catch (\Throwable $e) {
            Log::warning('Entry search sync failed', ['message' => $e->getMessage()]);
        }
    }
}

<?php

namespace App\Observers;

use App\Models\Entry;
use Illuminate\Support\Facades\Log;

final class EntrySearchObserver
{
    public function updated(Entry $entry): void
    {
        $this->syncEntryToScout($entry);
    }

    public function deleted(Entry $entry): void
    {
        if (!method_exists($entry, 'unsearchable')) {
            return;
        }
        try {
            $entry->unsearchable();
            Log::info('Entry removed from search index (deleted)', ['entry_id' => $entry->id]);
        } catch (\Throwable $e) {
            Log::warning('Entry search unindex failed on delete', ['entry_id' => $entry->id, 'message' => $e->getMessage()]);
        }
    }

    private function syncEntryToScout(Entry $entry): void
    {
        if (!method_exists($entry, 'searchable') || !method_exists($entry, 'unsearchable')) {
            return;
        }

        try {
            if (($entry->status ?? '') === 'published') {
                $entry->searchable();
                Log::info('Entry synced to search index (updated)', ['entry_id' => $entry->id]);
            } else {
                $entry->unsearchable();
                Log::info('Entry removed from search index (unpublished)', ['entry_id' => $entry->id]);
            }
        } catch (\Throwable $e) {
            Log::warning('Entry search sync failed on update', ['entry_id' => $entry->id, 'message' => $e->getMessage()]);
        }
    }
}

<?php

namespace App\Modules\Content\Services;

use App\Models\Entry;
use App\Modules\Content\Contracts\CacheInvalidatorInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class CacheInvalidator implements CacheInvalidatorInterface
{
    public function invalidateEntry(int $spaceId, string $collectionHandle, Entry $entry): void
    {
        try {
            $driver = config('cache.default');
            $supportsTags = in_array($driver, ['redis', 'memcached'], true);

            if ($supportsTags) {
                $tags = [
                    "space:{$spaceId}",
                    "collection:{$spaceId}:{$collectionHandle}",
                    "entry:{$spaceId}:{$collectionHandle}:{$entry->id}",
                ];

                Cache::tags($tags)->flush();
                Log::info('Cache invalidated via tags', [
                    'space_id' => $spaceId,
                    'collection' => $collectionHandle,
                    'entry_id' => $entry->id,
                    'tags' => $tags,
                ]);
            } else {
                Cache::flush();
                Log::info('Cache flushed (tags not supported)', [
                    'space_id' => $spaceId,
                    'collection' => $collectionHandle,
                    'entry_id' => $entry->id,
                    'driver' => $driver,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Cache invalidation error', [
                'space_id' => $spaceId,
                'collection' => $collectionHandle,
                'entry_id' => $entry->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}

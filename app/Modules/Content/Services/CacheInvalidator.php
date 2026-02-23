<?php

namespace App\Modules\Content\Services;

use App\Models\Entry;
use App\Modules\Content\Contracts\CacheInvalidatorInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class CacheInvalidator implements CacheInvalidatorInterface
{
    private const NS_SPACE = 'cache_ns:space:';
    private const NS_COLLECTION = 'cache_ns:collection:';
    private const NS_ENTRY = 'cache_ns:entry:';

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
                $spaceNs = self::NS_SPACE . $spaceId;
                $collectionNs = self::NS_COLLECTION . $spaceId . ':' . $collectionHandle;
                $entryNs = self::NS_ENTRY . $spaceId . ':' . $collectionHandle . ':' . $entry->id;

                Cache::increment($spaceNs);
                Cache::increment($collectionNs);
                Cache::increment($entryNs);

                if ((int) Cache::get($spaceNs, 0) <= 0) {
                    Cache::forever($spaceNs, 1);
                }
                if ((int) Cache::get($collectionNs, 0) <= 0) {
                    Cache::forever($collectionNs, 1);
                }
                if ((int) Cache::get($entryNs, 0) <= 0) {
                    Cache::forever($entryNs, 1);
                }

                Log::info('Cache invalidated via namespace versions', [
                    'space_id' => $spaceId,
                    'collection' => $collectionHandle,
                    'entry_id' => $entry->id,
                    'driver' => $driver,
                    'space_ns' => Cache::get($spaceNs, 1),
                    'collection_ns' => Cache::get($collectionNs, 1),
                    'entry_ns' => Cache::get($entryNs, 1),
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

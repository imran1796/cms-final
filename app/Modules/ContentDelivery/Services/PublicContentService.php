<?php

namespace App\Modules\ContentDelivery\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Support\Exceptions\NotFoundApiException;
use App\Modules\ContentDelivery\Services\Interfaces\PublicContentServiceInterface;
use App\Modules\ContentDelivery\Repositories\Interfaces\PublicContentRepositoryInterface;
use App\Modules\ContentDelivery\Support\PublicQueryParser;
use App\Modules\ContentDelivery\Support\PublicPopulateService;
use App\Modules\ContentDelivery\Support\LocaleProjector;
use App\Modules\Content\Repositories\Interfaces\CollectionRepositoryInterface;

final class PublicContentService implements PublicContentServiceInterface
{
    private const NS_SPACE = 'cache_ns:space:';
    private const NS_COLLECTION = 'cache_ns:collection:';
    private const NS_ENTRY = 'cache_ns:entry:';

    public function __construct(
        private readonly PublicContentRepositoryInterface $repo,
        private readonly CollectionRepositoryInterface $collections,
        private readonly PublicPopulateService $populate,
    ) {
    }

    public function list(string $collectionHandle, array $query): array
    {
        $spaceId = $this->requireSpaceId();

        $collection = $this->collections->findByHandle($spaceId, $collectionHandle);
        if (!$collection) {
            throw new NotFoundApiException('Resource not found');
        }

        $parsed = PublicQueryParser::parse($query);

        $cacheKey = $this->cacheKey($spaceId, $collectionHandle, 'list', $parsed);
        $ttl = (int) (config('cms.public.cache_ttl_seconds') ?? 30);
        $tags = $this->getCacheTags($spaceId, $collectionHandle);

        if ($tags) {
            return Cache::tags($tags)->remember($cacheKey, $ttl, function () use ($spaceId, $collection, $parsed, $collectionHandle) {
                return $this->buildListResult($spaceId, $collection, $parsed, $collectionHandle);
            });
        }

        return Cache::remember($cacheKey, $ttl, function () use ($spaceId, $collection, $parsed, $collectionHandle) {
            return $this->buildListResult($spaceId, $collection, $parsed, $collectionHandle);
        });
    }

    public function get(string $collectionHandle, int $id, array $query): array
    {
        $spaceId = $this->requireSpaceId();

        $collection = $this->collections->findByHandle($spaceId, $collectionHandle);
        if (!$collection) {
            throw new NotFoundApiException('Resource not found');
        }

        $parsed = PublicQueryParser::parse($query);

        $cacheKey = $this->cacheKey($spaceId, $collectionHandle, "get:$id", $parsed);
        $ttl = (int) (config('cms.public.cache_ttl_seconds') ?? 30);
        $tags = $this->getCacheTags($spaceId, $collectionHandle, $id);

        if ($tags) {
            return Cache::tags($tags)->remember($cacheKey, $ttl, function () use ($spaceId, $collection, $parsed, $id, $collectionHandle) {
                return $this->buildGetResult($spaceId, $collection, $parsed, $id, $collectionHandle);
            });
        }

        return Cache::remember($cacheKey, $ttl, function () use ($spaceId, $collection, $parsed, $id, $collectionHandle) {
            return $this->buildGetResult($spaceId, $collection, $parsed, $id, $collectionHandle);
        });
    }

    private function shapeEntry($entry, array $parsed): array
    {
        $arr = is_array($entry) ? $entry : $entry->toArray();

        if ($parsed['locale']) {
            $arr['data'] = LocaleProjector::project((array)($arr['data'] ?? []), $parsed['locale']);
        }

        if (!empty($parsed['fields'])) {
            $projected = [];
            foreach ($parsed['fields'] as $f) {
                if ($f === 'id') $projected['id'] = $arr['id'] ?? null;
                elseif ($f === 'created_at') $projected['created_at'] = $arr['created_at'] ?? null;
                elseif ($f === 'updated_at') $projected['updated_at'] = $arr['updated_at'] ?? null;
                elseif ($f === 'published_at') $projected['published_at'] = $arr['published_at'] ?? null;
                else {
                    $projected[$f] = data_get($arr['data'] ?? [], $f);
                }
            }
            return $projected;
        }

        return [
            'id' => $arr['id'] ?? null,
            'published_at' => $arr['published_at'] ?? null,
            'data' => (array)($arr['data'] ?? []),
        ];
    }

    private function requireSpaceId(): int
    {
        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null || $spaceId <= 0) {
            throw new \App\Support\Exceptions\ValidationApiException('X-Space-Id header is required', [
                'space_id' => ['Missing X-Space-Id'],
            ]);
        }
        return $spaceId;
    }

    private function cacheKey(int $spaceId, string $handle, string $op, array $parsed): string
    {
        $spaceNs = $this->namespaceVersion(self::NS_SPACE . $spaceId);
        $collectionNs = $this->namespaceVersion(self::NS_COLLECTION . $spaceId . ':' . $handle);
        $entryNs = null;
        if (str_starts_with($op, 'get:')) {
            $entryId = (int) substr($op, 4);
            if ($entryId > 0) {
                $entryNs = $this->namespaceVersion(self::NS_ENTRY . $spaceId . ':' . $handle . ':' . $entryId);
            }
        }

        $hash = sha1(json_encode($parsed));
        $nsPart = "ns:s{$spaceNs}:c{$collectionNs}";
        if ($entryNs !== null) {
            $nsPart .= ":e{$entryNs}";
        }
        return "public:space:$spaceId:$handle:$op:$nsPart:$hash";
    }

    private function buildListResult(int $spaceId, $collection, array $parsed, string $collectionHandle): array
    {
        $rows = $this->repo->listPublished($spaceId, (int) $collection->id, $parsed);

        $items = [];
        foreach ($rows as $row) {
            $item = $this->shapeEntry($row, $parsed);
            $items[] = $item;
        }

        if ($parsed['populate']) {
            $items = $this->populate->apply($spaceId, $collection, $items, $parsed['populate'], $parsed['max_depth']);
        }

        Log::info('Public content list', [
            'space_id' => $spaceId,
            'handle' => $collectionHandle,
            'count' => count($items),
        ]);

        return [
            'items' => $items,
            'meta' => [
                'limit' => $parsed['limit'],
                'sort' => $parsed['sort'],
            ],
        ];
    }

    private function buildGetResult(int $spaceId, $collection, array $parsed, int $id, string $collectionHandle): array
    {
        $row = $this->repo->getPublished($spaceId, (int) $collection->id, $id);
        if (!$row) {
            throw new NotFoundApiException('Resource not found');
        }

        $item = $this->shapeEntry($row, $parsed);

        if ($parsed['populate']) {
            $items = $this->populate->apply($spaceId, $collection, [$item], $parsed['populate'], $parsed['max_depth']);
            $item = $items[0] ?? $item;
        }

        Log::info('Public content get', [
            'space_id' => $spaceId,
            'handle' => $collectionHandle,
            'entry_id' => $id,
        ]);

        return $item;
    }

    private function getCacheTags(int $spaceId, string $collectionHandle, ?int $entryId = null): ?array
    {
        $driver = config('cache.default');
        if (!in_array($driver, ['redis', 'memcached'], true)) {
            return null;
        }

        $tags = [
            "space:{$spaceId}",
            "collection:{$spaceId}:{$collectionHandle}",
        ];

        if ($entryId !== null) {
            $tags[] = "entry:{$spaceId}:{$collectionHandle}:{$entryId}";
        }

        return $tags;
    }

    private function namespaceVersion(string $key): int
    {
        $value = Cache::get($key, 1);
        $version = is_numeric($value) ? (int) $value : 1;
        return $version > 0 ? $version : 1;
    }
}

<?php

namespace App\Modules\Search\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Models\Entry;
use App\Modules\Search\Services\Interfaces\SearchServiceInterface;
use App\Modules\Search\DTO\SearchQuery;
use App\Support\Exceptions\NotFoundApiException;
use App\Support\Exceptions\ForbiddenApiException;

use App\Modules\Content\Repositories\Interfaces\CollectionRepositoryInterface;
use App\Modules\Content\Repositories\Interfaces\EntryRepositoryInterface;

final class SearchService implements SearchServiceInterface
{
    public function __construct(
        private readonly CollectionRepositoryInterface $collections,
        private readonly EntryRepositoryInterface $entries,
    ) {}

    public function search(string $collectionHandle, SearchQuery $query): array
    {
        $spaceId = $this->requireSpaceId();

        $collection = $this->collections->findByHandle($spaceId, $collectionHandle);
        if (!$collection) throw new NotFoundApiException('Collection not found');

        $settings = (array) ($collection->settings ?? []);
        $searchCfg = (array) ($settings['search'] ?? []);

        if (array_key_exists('enabled', $searchCfg) && $searchCfg['enabled'] === false) {
            throw new ForbiddenApiException('Search disabled for this collection');
        }

        $engine = config('cms_search.engine', 'auto');
        $useScout = ($engine !== 'db')
            && (config('scout.driver') !== 'null' && config('scout.driver') !== 'collection')
            && method_exists(Entry::class, 'search');

        if ($useScout) {
            try {
                return $this->searchViaScout((int) $collection->id, $spaceId, $query, $searchCfg);
            } catch (\Throwable $e) {
                Log::warning('Scout search failed, fallback', ['message' => $e->getMessage()]);
                if (!config('cms_search.fallback_db', true)) {
                    throw $e;
                }
            }
        }

        return $this->searchViaDbFallback((int) $collection->id, $spaceId, $query, $searchCfg);
    }

    private function searchViaScout(int $collectionId, int $spaceId, SearchQuery $query, array $searchCfg): array
    {
        $filterable = $this->allowedFilterableAttributes($searchCfg);
        $sortable = $this->allowedSortableAttributes($searchCfg);

        $builder = Entry::search($query->q)
            ->where('space_id', $spaceId)
            ->where('collection_id', $collectionId)
            ->where('status', 'published');

        foreach ($query->filters as $field => $value) {
            if (in_array($field, $filterable, true)) {
                $builder->where($field, $value);
            }
        }

        if ($query->sort) {
            $dir = 'asc';
            $col = $query->sort;
            if (str_starts_with($col, '-')) {
                $dir = 'desc';
                $col = substr($col, 1);
            }
            if (in_array($col, $sortable, true)) {
                $builder->orderBy($col, $dir);
            }
        } else {
            $builder->orderBy('id', 'desc');
        }

        $builder->take($query->limit);
        if ($query->offset > 0) {
            $builder->options(array_merge($builder->options ?? [], ['offset' => $query->offset]));
        }

        $results = $builder->get();
        $items = $results->map(fn ($e) => $e->toArray())->all();

        return [
            'items' => $items,
            'meta' => [
                'engine' => 'scout',
                'total' => $results->count(),
                'count' => count($items),
                'limit' => $query->limit,
                'offset' => $query->offset,
            ],
        ];
    }

    private function allowedFilterableAttributes(array $searchCfg): array
    {
        $attrs = $searchCfg['filterable_attributes'] ?? config('cms_search.default_filterable_attributes', ['status']);
        return is_array($attrs) ? array_values($attrs) : ['status'];
    }

    private function allowedSortableAttributes(array $searchCfg): array
    {
        $attrs = $searchCfg['sortable_attributes'] ?? config('cms_search.default_sortable_attributes', ['created_at', 'published_at', 'id']);
        return is_array($attrs) ? array_values($attrs) : ['created_at', 'published_at', 'id'];
    }

    private function searchViaDbFallback(int $collectionId, int $spaceId, SearchQuery $query, array $searchCfg = []): array
    {
        $filterable = $this->allowedFilterableAttributes($searchCfg);
        $sortable = $this->allowedSortableAttributes($searchCfg);

        $qb = DB::table('entries')
            ->where('space_id', $spaceId)
            ->where('collection_id', $collectionId)
            ->where('status', 'published');

        $q = $query->q;
        $like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
        $qb->where(function ($w) use ($like) {
            $w->where('title', 'like', '%' . $like . '%')
                ->orWhere('slug', 'like', '%' . $like . '%');
        });

        foreach ($query->filters as $field => $value) {
            if (in_array($field, $filterable, true)) {
                $qb->where($field, $value);
            }
        }

        if ($query->sort) {
            $dir = 'asc';
            $col = $query->sort;
            if (str_starts_with($col, '-')) {
                $dir = 'desc';
                $col = substr($col, 1);
            }
            if (in_array($col, $sortable, true)) {
                $qb->orderBy($col, $dir);
            }
        } else {
            $qb->orderByDesc('id');
        }

        $total = (clone $qb)->count();

        $rows = $qb
            ->offset($query->offset)
            ->limit($query->limit)
            ->get();

        $items = $rows->map(function ($r) {
            $arr = (array) $r;
            if (isset($arr['data']) && is_string($arr['data'])) {
                $arr['data'] = json_decode($arr['data'], true) ?? $arr['data'];
            }
            return $arr;
        })->all();

        return [
            'items' => $items,
            'meta' => [
                'engine' => 'db',
                'total' => $total,
                'count' => count($rows),
                'limit' => $query->limit,
                'offset' => $query->offset,
            ],
        ];
    }

    private function requireSpaceId(): int
    {
        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null || $spaceId <= 0) {
            throw new \App\Support\Exceptions\ValidationApiException('Validation failed', [
                'space_id' => ['Missing X-Space-Id'],
            ]);
        }
        return $spaceId;
    }
}

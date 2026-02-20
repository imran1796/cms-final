<?php

namespace App\Modules\Content\Repositories;

use App\Models\Entry;
use App\Modules\Content\Repositories\Interfaces\EntryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

final class EntryRepository implements EntryRepositoryInterface
{
    private const ALLOWED_SORT_FIELDS = ['id', 'created_at', 'updated_at', 'published_at', 'title', 'slug'];

    public function paginate(int $spaceId, int $collectionId, int $perPage = 20): LengthAwarePaginator
    {
        return Entry::query()
            ->where('space_id', $spaceId)
            ->where('collection_id', $collectionId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function listFiltered(int $spaceId, int $collectionId, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Entry::query()
            ->where('space_id', $spaceId)
            ->where('collection_id', $collectionId);

        $status = $filters['status'] ?? 'all';
        if ($status === 'draft') {
            $query->where('status', 'draft');
        } elseif ($status === 'published') {
            $query->where('status', 'published');
        } elseif ($status === 'scheduled') {
            $query->where(function ($q) {
                $q->where('status', 'scheduled');
                // Legacy fallback intentionally disabled after migration to real scheduled status.
                // Re-enable this block only if you still need to treat draft+future published_at as scheduled:
                // $q->orWhere(function ($legacy) {
                //     $legacy->where('status', 'draft')
                //         ->where('published_at', '>', Carbon::now());
                // });
            });
        } elseif ($status === 'archived') {
            $query->where('status', 'archived');
        }

        $search = $filters['search'] ?? null;
        if ($search !== null && $search !== '') {
            $term = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('slug', 'like', $term);
            });
        }

        $dataFilter = $filters['filter'] ?? [];
        if (!empty($dataFilter) && is_array($dataFilter)) {
            foreach ($dataFilter as $key => $value) {
                $query->where('data->' . $key, '=', $value);
            }
        }

        $sort = $filters['sort'] ?? '-id';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');
        if (in_array($field, self::ALLOWED_SORT_FIELDS, true)) {
            $query->orderBy($field, $direction);
        } else {
            $query->orderByDesc('id');
        }

        return $query->paginate($perPage);
    }

    public function findOrFail(int $spaceId, int $collectionId, int $id): Entry
    {
        return Entry::query()
            ->where('space_id', $spaceId)
            ->where('collection_id', $collectionId)
            ->where('id', $id)
            ->firstOrFail();
    }

    public function create(array $data): Entry
    {
        return Entry::create($data);
    }

    public function update(Entry $entry, array $data): Entry
    {
        $entry->update($data);
        return $entry->refresh();
    }

    public function delete(Entry $entry): void
    {
        $entry->delete();
    }
}

<?php

namespace App\Modules\ContentDelivery\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Entry;
use App\Modules\ContentDelivery\Repositories\Interfaces\PublicContentRepositoryInterface;

final class PublicContentRepository implements PublicContentRepositoryInterface
{
    public function listPublished(int $spaceId, int $collectionId, array $parsed): array
    {
        $q = Entry::query()
            ->where('space_id', $spaceId)
            ->where('collection_id', $collectionId)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', Carbon::now());

        foreach ($parsed['filters'] as $field => $value) {
            $driver = DB::getDriverName();

            if ($driver === 'mysql') {
                $q->whereRaw("JSON_EXTRACT(data, ?) = JSON_QUOTE(?)", ["$.{$field}", (string)$value]);
            } else {
                $q->whereRaw("json_extract(data, ?) = ?", ["$.{$field}", (string)$value]);
            }
        }

        if ($parsed['sort']) {
            $dir = str_starts_with($parsed['sort'], '-') ? 'desc' : 'asc';
            $col = ltrim($parsed['sort'], '-');

            $allowed = ['created_at', 'updated_at', 'published_at', 'id'];
            if (in_array($col, $allowed, true)) {
                $q->orderBy($col, $dir);
            } else {
                $q->orderBy('id', 'desc');
            }
        } else {
            $q->orderBy('id', 'desc');
        }

        $rows = $q->limit($parsed['limit'])->get();
        return $rows->all();
    }

    public function getPublished(int $spaceId, int $collectionId, int $id): ?object
    {
        return Entry::query()
            ->where('space_id', $spaceId)
            ->where('collection_id', $collectionId)
            ->where('id', $id)
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', Carbon::now())
            ->first();
    }
}

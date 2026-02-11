<?php

namespace App\Modules\Content\Repositories;

use App\Models\Collection;
use App\Modules\Content\Repositories\Interfaces\CollectionRepositoryInterface;
use Illuminate\Support\Collection as SupportCollection;

final class CollectionRepository implements CollectionRepositoryInterface
{
    public function list(?int $spaceId): SupportCollection
    {
        return Collection::query()
            ->where('space_id', $spaceId)
            ->orderByDesc('id')
            ->get();
    }

    public function create(array $data): Collection
    {
        return Collection::create($data);
    }

    public function findOrFailForSpace(int $spaceId, int $id): Collection
    {
        return Collection::query()
            ->where('space_id', $spaceId)
            ->where('id', $id)
            ->firstOrFail();
    }

    public function findByHandle(int $spaceId, string $handle): ?Collection
    {
        return Collection::query()
            ->where('space_id', $spaceId)
            ->where('handle', $handle)
            ->first();
    }

    public function update(Collection $collection, array $data): Collection
    {
        $collection->update($data);
        return $collection->refresh();
    }

    public function delete(Collection $collection): void
    {
        $collection->delete();
    }
}

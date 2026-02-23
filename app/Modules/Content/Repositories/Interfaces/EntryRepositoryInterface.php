<?php

declare(strict_types=1);

namespace App\Modules\Content\Repositories\Interfaces;

use App\Models\Entry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EntryRepositoryInterface
{
    public function paginate(int $spaceId, int $collectionId, int $perPage = 20): LengthAwarePaginator;

    public function listFiltered(int $spaceId, int $collectionId, array $filters, int $perPage = 20): LengthAwarePaginator;

    public function findOrFail(int $spaceId, int $collectionId, int $id): Entry;

    public function findPublishedById(int $spaceId, int $collectionId, int $id): ?Entry;

    public function create(array $data): Entry;

    public function update(Entry $entry, array $data): Entry;

    public function delete(Entry $entry): void;
}

<?php

namespace App\Modules\Content\Services;

use App\Models\Entry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EntryServiceInterface
{
    public function list(string $collectionHandle, array $query): LengthAwarePaginator;

    public function create(string $collectionHandle, array $input): Entry;

    public function get(string $collectionHandle, int $id, array $query): array;

    public function update(string $collectionHandle, int $id, array $input): Entry;

    public function delete(string $collectionHandle, int $id): void;
}

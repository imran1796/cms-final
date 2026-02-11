<?php

declare(strict_types=1);

namespace App\Modules\Content\Repositories\Interfaces;

use App\Models\Collection;
use Illuminate\Support\Collection as SupportCollection;

interface CollectionRepositoryInterface
{
    public function list(?int $spaceId): SupportCollection;

    public function create(array $data): Collection;

    public function findOrFailForSpace(int $spaceId, int $id): Collection;

    public function findByHandle(int $spaceId, string $handle): ?Collection;

    public function update(Collection $collection, array $data): Collection;

    public function delete(Collection $collection): void;
}

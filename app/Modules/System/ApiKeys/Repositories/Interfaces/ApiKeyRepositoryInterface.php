<?php

namespace App\Modules\System\ApiKeys\Repositories\Interfaces;

use App\Models\ApiKey;
use Illuminate\Support\Collection;

interface ApiKeyRepositoryInterface
{
    public function listBySpace(int $spaceId): Collection;

    public function create(array $data): ApiKey;

    public function findOrFailForSpace(int $spaceId, int $id): ApiKey;

    public function update(ApiKey $key, array $data): ApiKey;

    public function delete(ApiKey $key): void;
}

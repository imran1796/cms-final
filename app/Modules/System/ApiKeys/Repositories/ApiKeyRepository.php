<?php

namespace App\Modules\System\ApiKeys\Repositories;

use App\Models\ApiKey;
use App\Modules\System\ApiKeys\Repositories\Interfaces\ApiKeyRepositoryInterface;
use Illuminate\Support\Collection;

final class ApiKeyRepository implements ApiKeyRepositoryInterface
{
    public function listBySpace(int $spaceId): Collection
    {
        return ApiKey::query()
            ->where('space_id', $spaceId)
            ->orderByDesc('id')
            ->get();
    }

    public function create(array $data): ApiKey
    {
        return ApiKey::create($data);
    }

    public function findOrFailForSpace(int $spaceId, int $id): ApiKey
    {
        return ApiKey::query()
            ->where('space_id', $spaceId)
            ->where('id', $id)
            ->firstOrFail();
    }

    public function update(ApiKey $key, array $data): ApiKey
    {
        $key->update($data);
        return $key->refresh();
    }

    public function delete(ApiKey $key): void
    {
        $key->delete();
    }
}

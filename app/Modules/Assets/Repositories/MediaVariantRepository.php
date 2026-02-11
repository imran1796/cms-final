<?php

namespace App\Modules\Assets\Repositories;

use App\Modules\Assets\Models\MediaVariant;
use App\Modules\Assets\Repositories\Interfaces\MediaVariantRepositoryInterface;

final class MediaVariantRepository implements MediaVariantRepositoryInterface
{
    public function findByKey(int $mediaId, string $transformKey): ?MediaVariant
    {
        return MediaVariant::query()
            ->where('media_id', $mediaId)
            ->where('transform_key', $transformKey)
            ->first();
    }

    public function create(array $data): MediaVariant
    {
        return MediaVariant::create($data);
    }
}

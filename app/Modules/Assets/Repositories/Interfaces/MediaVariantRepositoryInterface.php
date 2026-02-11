<?php

namespace App\Modules\Assets\Repositories\Interfaces;

use App\Modules\Assets\Models\MediaVariant;

interface MediaVariantRepositoryInterface
{
    public function findByKey(int $mediaId, string $transformKey): ?MediaVariant;
    public function create(array $data): MediaVariant;
}

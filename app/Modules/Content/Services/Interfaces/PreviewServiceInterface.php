<?php

declare(strict_types=1);

namespace App\Modules\Content\Services\Interfaces;

use App\Models\Entry;

interface PreviewServiceInterface
{
    public function preview(int $spaceId, string $collectionHandle, int $id, string $token): Entry;
}

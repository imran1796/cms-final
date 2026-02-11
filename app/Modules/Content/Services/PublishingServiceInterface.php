<?php

namespace App\Modules\Content\Services;

use App\Models\Entry;

interface PublishingServiceInterface
{
    public function publish(string $collectionHandle, int $id): Entry;
    public function unpublish(string $collectionHandle, int $id): Entry;

    public function publishScheduled(): int;
}

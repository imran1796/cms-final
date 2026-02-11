<?php

namespace App\Modules\Content\Revisions\Services\Interfaces;

use Illuminate\Support\Collection;

interface RevisionServiceInterface
{
    public function list(string $collectionHandle, int $entryId): Collection;

    public function restore(string $collectionHandle, int $entryId, int $revisionId): array;

    public function createOnUpdate(string $collectionHandle, int $entryId, array $beforeSnapshot, array $afterData): void;
}

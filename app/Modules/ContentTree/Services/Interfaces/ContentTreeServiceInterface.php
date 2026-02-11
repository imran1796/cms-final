<?php

namespace App\Modules\ContentTree\Services\Interfaces;

interface ContentTreeServiceInterface
{
    public function getTree(int $spaceId, string $collectionHandle): array;

    public function ensureNodeForEntry(int $spaceId, int $collectionId, int $entryId): void;

    public function moveEntry(int $spaceId, string $collectionHandle, int $entryId, ?int $newParentEntryId, ?int $position, ?int $actorId): void;

    public function reorder(int $spaceId, string $collectionHandle, ?int $parentEntryId, array $entryIds, ?int $actorId): void;
}

<?php

namespace App\Modules\ContentTree\Repositories\Interfaces;

use Illuminate\Support\Collection;

interface ContentTreeRepositoryInterface
{
    public function listNodes(int $spaceId, int $collectionId, ?int $limit = null, int $skip = 0): Collection;

    public function findNodeByEntry(int $spaceId, int $collectionId, int $entryId);

    public function createNode(array $data);

    public function updateNode(int $id, array $data): void;

    public function deleteNodesForCollection(int $spaceId, int $collectionId): void;

    public function siblingsMaxPosition(int $spaceId, int $collectionId, ?int $parentNodeId): int;

    public function reorderSiblings(int $spaceId, int $collectionId, ?int $parentNodeId, array $entryIds): void;
}

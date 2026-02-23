<?php

namespace App\Modules\ContentTree\Repositories;

use App\Modules\ContentTree\Models\ContentTreeNode;
use App\Modules\ContentTree\Repositories\Interfaces\ContentTreeRepositoryInterface;
use Illuminate\Support\Collection;

final class ContentTreeRepository implements ContentTreeRepositoryInterface
{
    public function listNodes(int $spaceId, int $collectionId, ?int $limit = null, int $skip = 0): Collection
    {
        $query = ContentTreeNode::query()
            ->where('space_id', $spaceId)
            ->where('collection_id', $collectionId)
            ->orderByRaw('parent_id is null desc') // root first (optional)
            ->orderBy('parent_id')
            ->orderBy('position');

        if ($skip > 0) {
            $query->skip($skip);
        }
        if ($limit !== null && $limit > 0) {
            $query->take($limit);
        }

        return $query->get();
    }

    public function findNodeByEntry(int $spaceId, int $collectionId, int $entryId)
    {
        return ContentTreeNode::query()
            ->where('space_id', $spaceId)
            ->where('collection_id', $collectionId)
            ->where('entry_id', $entryId)
            ->first();
    }

    public function createNode(array $data)
    {
        return ContentTreeNode::create($data);
    }

    public function updateNode(int $id, array $data): void
    {
        ContentTreeNode::query()->whereKey($id)->update($data);
    }

    public function deleteNodesForCollection(int $spaceId, int $collectionId): void
    {
        ContentTreeNode::query()
            ->where('space_id', $spaceId)
            ->where('collection_id', $collectionId)
            ->delete();
    }

    public function siblingsMaxPosition(int $spaceId, int $collectionId, ?int $parentNodeId): int
    {
        $q = ContentTreeNode::query()
            ->where('space_id', $spaceId)
            ->where('collection_id', $collectionId);

        if ($parentNodeId === null) {
            $q->whereNull('parent_id');
        } else {
            $q->where('parent_id', $parentNodeId);
        }

        return (int) ($q->max('position') ?? 0);
    }

    public function reorderSiblings(int $spaceId, int $collectionId, ?int $parentNodeId, array $entryIds): void
    {
        foreach (array_values($entryIds) as $pos => $entryId) {
            $q = ContentTreeNode::query()
                ->where('space_id', $spaceId)
                ->where('collection_id', $collectionId)
                ->where('entry_id', $entryId);

            if ($parentNodeId === null) {
                $q->whereNull('parent_id');
            } else {
                $q->where('parent_id', $parentNodeId);
            }

            $q->update(['position' => $pos]);
        }
    }
}

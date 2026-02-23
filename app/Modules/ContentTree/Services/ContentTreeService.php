<?php

namespace App\Modules\ContentTree\Services;

use App\Modules\Content\Repositories\Interfaces\CollectionRepositoryInterface;
use App\Modules\ContentTree\Repositories\Interfaces\ContentTreeRepositoryInterface;
use App\Modules\ContentTree\Services\Interfaces\ContentTreeServiceInterface;
use App\Modules\System\Audit\Services\Interfaces\AuditLogServiceInterface;
use App\Support\Exceptions\DomainApiException;
use App\Support\Exceptions\ForbiddenApiException;
use App\Support\Exceptions\NotFoundApiException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ContentTreeService implements ContentTreeServiceInterface
{
    public function __construct(
        private readonly ContentTreeRepositoryInterface $repo,
        private readonly CollectionRepositoryInterface $collections,
        private readonly AuditLogServiceInterface $audit,
    ) {}

    public function getTree(int $spaceId, string $collectionHandle, array $params = []): array
    {
        $collection = $this->collections->findByHandle($spaceId, $collectionHandle);
        if (!$collection) {
            throw new NotFoundApiException('Collection not found');
        }
        if ($collection->type !== 'tree') {
            throw new DomainApiException('Collection is not a tree type');
        }

        $limit = isset($params['limit']) && is_numeric($params['limit']) ? (int) $params['limit'] : 1000;
        $limit = max(1, min(5000, $limit));
        $skip = isset($params['skip']) && is_numeric($params['skip']) ? (int) $params['skip'] : 0;
        $skip = max(0, $skip);

        $nodes = $this->repo->listNodes($spaceId, (int)$collection->id, $limit, $skip);

        $byId = [];
        $childrenMap = [];
        foreach ($nodes as $n) {
            $byId[$n->id] = [
                'node_id'   => $n->id,
                'entry_id'  => $n->entry_id,
                'parent_id' => $n->parent_id,
                'position'  => $n->position,
                'children'  => [],
            ];
            $childrenMap[$n->parent_id ?? 0][] = $n->id;
        }

        $entryIds = $nodes->pluck('entry_id')->unique()->values()->all();
        $collectionId = (int) $collection->id;
        $entries = \App\Models\Entry::query()
            ->where('space_id', $spaceId)
            ->where('collection_id', $collectionId)
            ->whereIn('id', $entryIds)
            ->get()
            ->keyBy('id');

        foreach ($byId as &$item) {
            $entry = $entries->get($item['entry_id']);
            $item['entry'] = $entry ? [
                'id' => $entry->id,
                'status' => $entry->status,
                'published_at' => $entry->published_at,
                'data' => $entry->data,
                'title' => is_array($entry->data) ? ($entry->data['title'] ?? null) : null,
            ] : null;
        }

        $build = function ($parentKey) use (&$build, &$byId, $childrenMap) {
            $list = [];
            foreach (($childrenMap[$parentKey] ?? []) as $nodeId) {
                $node = $byId[$nodeId];
                $node['children'] = $build($nodeId);
                $list[] = $node;
            }
            return $list;
        };

        return $build(0);
    }

    public function ensureNodeForEntry(int $spaceId, int $collectionId, int $entryId): void
    {
        $existing = $this->repo->findNodeByEntry($spaceId, $collectionId, $entryId);
        if ($existing) {
            return;
        }

        $maxPos = $this->repo->siblingsMaxPosition($spaceId, $collectionId, null);

        $this->repo->createNode([
            'space_id' => $spaceId,
            'collection_id' => $collectionId,
            'entry_id' => $entryId,
            'parent_id' => null,
            'position' => $maxPos + 1,
            'path' => null,
        ]);
    }

    public function moveEntry(int $spaceId, string $collectionHandle, int $entryId, ?int $newParentEntryId, ?int $position, ?int $actorId): void
    {
        DB::beginTransaction();

        try {
            $collection = $this->collections->findByHandle($spaceId, $collectionHandle);
            if (!$collection) {
                throw new NotFoundApiException('Collection not found');
            }
            if ($collection->type !== 'tree') {
                throw new DomainApiException('Collection is not a tree type');
            }

            $node = $this->repo->findNodeByEntry($spaceId, (int)$collection->id, $entryId);
            if (!$node) {
                throw new NotFoundApiException('Tree node not found for entry');
            }

            $newParentNodeId = null;
            if ($newParentEntryId !== null) {
                $parentNode = $this->repo->findNodeByEntry($spaceId, (int)$collection->id, $newParentEntryId);
                if (!$parentNode) {
                    throw new NotFoundApiException('Parent node not found');
                }

                if ($this->isDescendant($spaceId, (int)$collection->id, $parentNode->id, $node->id)) {
                    throw new ForbiddenApiException('Cannot move node into its own subtree');
                }

                $newParentNodeId = (int)$parentNode->id;
            }

            if ($position === null) {
                $maxPos = $this->repo->siblingsMaxPosition($spaceId, (int)$collection->id, $newParentNodeId);
                $position = $maxPos + 1;
            }

            $this->repo->updateNode((int)$node->id, [
                'parent_id' => $newParentNodeId,
                'position' => $position,
            ]);

            $this->audit->write(
                action: 'tree.move',
                resource: $collectionHandle.':'.$entryId,
                diff: [
                    'parent_entry_id' => $newParentEntryId,
                    'position' => $position,
                ],
                spaceId: $spaceId,
                actorId: $actorId
            );

            Log::info('Tree move', [
                'space_id' => $spaceId,
                'collection' => $collectionHandle,
                'entry_id' => $entryId,
                'parent_entry_id' => $newParentEntryId,
                'position' => $position,
                'actor_id' => $actorId,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function reorder(int $spaceId, string $collectionHandle, ?int $parentEntryId, array $entryIds, ?int $actorId): void
    {
        DB::beginTransaction();

        try {
            $collection = $this->collections->findByHandle($spaceId, $collectionHandle);
            if (!$collection) {
                throw new NotFoundApiException('Collection not found');
            }
            if ($collection->type !== 'tree') {
                throw new DomainApiException('Collection is not a tree type');
            }

            $parentNodeId = null;
            if ($parentEntryId !== null) {
                $parentNode = $this->repo->findNodeByEntry($spaceId, (int)$collection->id, $parentEntryId);
                if (!$parentNode) {
                    throw new NotFoundApiException('Parent node not found');
                }
                $parentNodeId = (int)$parentNode->id;
            }

            $this->repo->reorderSiblings($spaceId, (int)$collection->id, $parentNodeId, $entryIds);

            $this->audit->write(
                action: 'tree.reorder',
                resource: 'collections:'.$collectionHandle,
                diff: [
                    'parent_entry_id' => $parentEntryId,
                    'order' => $entryIds,
                ],
                spaceId: $spaceId,
                actorId: $actorId
            );

            Log::info('Tree reorder', [
                'space_id' => $spaceId,
                'collection' => $collectionHandle,
                'parent_entry_id' => $parentEntryId,
                'actor_id' => $actorId,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function isDescendant(int $spaceId, int $collectionId, int $candidateParentNodeId, int $nodeId): bool
    {
        $current = \App\Modules\ContentTree\Models\ContentTreeNode::query()
            ->where('space_id', $spaceId)
            ->where('collection_id', $collectionId)
            ->whereKey($candidateParentNodeId)
            ->first();

        while ($current) {
            if ((int)$current->id === (int)$nodeId) {
                return true;
            }
            if ($current->parent_id === null) {
                break;
            }

            $current = \App\Modules\ContentTree\Models\ContentTreeNode::query()
                ->where('space_id', $spaceId)
                ->where('collection_id', $collectionId)
                ->whereKey((int)$current->parent_id)
                ->first();
        }

        return false;
    }
}

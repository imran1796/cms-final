<?php

namespace App\Modules\ContentDelivery\Support;

use App\Modules\Content\Repositories\Interfaces\CollectionRepositoryInterface;
use App\Modules\Content\Repositories\Interfaces\EntryRepositoryInterface;

final class PublicPopulateService
{
    public function __construct(
        private readonly CollectionRepositoryInterface $collections,
        private readonly EntryRepositoryInterface $entries,
    ) {
    }

    public function apply(int $spaceId, $collection, array $items, array $populateFields, int $maxDepth): array
    {
        if ($maxDepth < 1) return $items;

        $fieldDefs = (array) ($collection->fields ?? []);
        $relationMap = [];
        foreach ($fieldDefs as $def) {
            if (($def['type'] ?? null) === 'relation' && !empty($def['name']) && !empty($def['target'])) {
                $relationMap[$def['name']] = $def;
            }
        }

        foreach ($items as &$item) {
            $data = (array) ($item['data'] ?? []);

            foreach ($populateFields as $f) {
                if (!isset($relationMap[$f])) {
                    continue;
                }

                $def = $relationMap[$f];
                $targetHandle = (string) $def['target'];
                $multiple = (bool) ($def['multiple'] ?? false);

                $targetCollection = $this->collections->findByHandle($spaceId, $targetHandle);
                if (!$targetCollection) continue;

                $val = $data[$f] ?? null;

                if ($multiple && is_array($val)) {
                    $pop = [];
                    foreach ($val as $id) {
                        $entry = $this->entries->findPublishedById($spaceId, (int)$targetCollection->id, (int)$id);
                        if ($entry) $pop[] = ['id' => $entry->id, 'data' => (array)$entry->data];
                    }
                    $data[$f] = $pop;
                } elseif (!$multiple && $val) {
                    $entry = $this->entries->findPublishedById($spaceId, (int)$targetCollection->id, (int)$val);
                    if ($entry) {
                        $data[$f] = ['id' => $entry->id, 'data' => (array)$entry->data];
                    }
                }
            }

            $item['data'] = $data;
        }

        return $items;
    }
}

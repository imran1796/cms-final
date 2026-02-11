<?php

namespace App\Modules\Assets\Repositories;

use App\Modules\Assets\Models\MediaFolder;
use App\Modules\Assets\Repositories\Interfaces\MediaFolderRepositoryInterface;

final class MediaFolderRepository implements MediaFolderRepositoryInterface
{
    public function find(int $spaceId, int $id): ?MediaFolder
    {
        return MediaFolder::query()
            ->where('space_id', $spaceId)
            ->where('id', $id)
            ->first();
    }

    public function listByParent(int $spaceId, ?int $parentId): array
    {
        $query = MediaFolder::query()
            ->where('space_id', $spaceId)
            ->orderBy('name');

        if ($parentId === null) {
            $query->whereNull('parent_id');
        } else {
            $query->where('parent_id', $parentId);
        }

        return $query->get()->all();
    }

    public function create(array $data): MediaFolder
    {
        return MediaFolder::create($data);
    }

    public function update(MediaFolder $folder, array $data): MediaFolder
    {
        $folder->fill($data);
        $folder->save();
        return $folder;
    }

    public function delete(MediaFolder $folder): void
    {
        $folder->delete();
    }
}

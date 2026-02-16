<?php

namespace App\Modules\Assets\Repositories;

use App\Modules\Assets\Models\Media;
use App\Modules\Assets\Repositories\Interfaces\MediaRepositoryInterface;

final class MediaRepository implements MediaRepositoryInterface
{
    public function list(int $spaceId): array
    {
        return Media::query()
            ->where('space_id', $spaceId)
            ->orderByDesc('id')
            ->get()
            ->all();
    }

    public function listByFolder(int $spaceId, ?int $folderId): array
    {
        $query = Media::query()
            ->where('space_id', $spaceId)
            ->orderBy('filename');

        if ($folderId === null) {
            $query->whereNull('folder_id');
        } else {
            $query->where('folder_id', $folderId);
        }

        return $query->get()->all();
    }

    public function listByFolderPaginated(int $spaceId, ?int $folderId, int $limit, int $skip): array
    {
        $query = Media::query()
            ->where('space_id', $spaceId);

        if ($folderId === null) {
            $query->whereNull('folder_id');
        } else {
            $query->where('folder_id', $folderId);
        }

        $total = (clone $query)->count();
        $items = (clone $query)
            ->orderBy('filename')
            ->skip($skip)
            ->take($limit)
            ->get()
            ->all();

        return ['items' => $items, 'total' => $total];
    }

    public function find(int $spaceId, int $id): ?Media
    {
        return Media::query()
            ->where('space_id', $spaceId)
            ->where('id', $id)
            ->first();
    }

    public function findForSpace(int $spaceId, int $id): ?Media
    {
        return Media::query()
            ->where('space_id', $spaceId)
            ->where('id', $id)
            ->first();
    }

    public function create(array $data): Media
    {
        return Media::create($data);
    }

    public function update(Media $media, array $data): Media
    {
        $media->fill($data);
        $media->save();
        return $media;
    }

    public function delete(Media $media): void
    {
        $media->delete();
    }
}

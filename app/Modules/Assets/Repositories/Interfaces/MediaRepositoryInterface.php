<?php

namespace App\Modules\Assets\Repositories\Interfaces;

use App\Modules\Assets\Models\Media;

interface MediaRepositoryInterface
{
    public function list(int $spaceId): array;

    public function listByFolder(int $spaceId, ?int $folderId): array;

    /**
     * Paginated, folder-scoped list for assets index.
     * Returns ['items' => Media[], 'total' => int].
     */
    public function listByFolderPaginated(int $spaceId, ?int $folderId, int $limit, int $skip): array;
    public function find(int $spaceId, int $id): ?Media;
    public function findForSpace(int $spaceId, int $id): ?Media;
    public function create(array $data): Media;
    public function update(Media $media, array $data): Media;
    public function delete(Media $media): void;
}

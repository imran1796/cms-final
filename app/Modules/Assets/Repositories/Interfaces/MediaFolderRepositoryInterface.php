<?php

namespace App\Modules\Assets\Repositories\Interfaces;

use App\Modules\Assets\Models\MediaFolder;

interface MediaFolderRepositoryInterface
{
    public function find(int $spaceId, int $id): ?MediaFolder;

    public function listByParent(int $spaceId, ?int $parentId): array;

    public function create(array $data): MediaFolder;

    public function update(MediaFolder $folder, array $data): MediaFolder;

    public function delete(MediaFolder $folder): void;
}

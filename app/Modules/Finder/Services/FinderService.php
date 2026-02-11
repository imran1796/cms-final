<?php

namespace App\Modules\Finder\Services;

use App\Modules\Assets\Services\Interfaces\AssetServiceInterface;
use App\Modules\Assets\Repositories\Interfaces\MediaFolderRepositoryInterface;
use App\Modules\Assets\Repositories\Interfaces\MediaRepositoryInterface;
use App\Modules\System\Authorization\Services\AuthorizationService;
use App\Support\Exceptions\NotFoundApiException;
use App\Support\Exceptions\ValidationApiException;

final class FinderService
{
    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly MediaFolderRepositoryInterface $folders,
        private readonly MediaRepositoryInterface $media,
        private readonly AssetServiceInterface $assets,
    ) {
    }

    public function index(?int $folderId): array
    {
        $spaceId = $this->requireSpaceId();
        $this->authz->requirePermission('manage_assets');

        $folderList = $this->folders->listByParent($spaceId, $folderId);
        $fileList = $this->media->listByFolder($spaceId, $folderId);

        return [
            'folders' => array_map(fn ($f) => $f->toArray(), $folderList),
            'files' => array_map(fn ($m) => $m->toArray(), $fileList),
        ];
    }

    public function createFolder(array $input): array
    {
        $this->authz->requirePermission('manage_assets');
        return $this->assets->createFolder($input);
    }

    public function renameFolder(int $id, string $name): array
    {
        $spaceId = $this->requireSpaceId();
        $this->authz->requirePermission('manage_assets');

        $folder = $this->folders->find($spaceId, $id);
        if (!$folder) {
            throw new NotFoundApiException('Folder not found');
        }

        $name = trim($name, '/');
        if ($name === '') {
            throw new ValidationApiException('Validation failed', ['name' => ['name cannot be empty']]);
        }

        $updated = $this->folders->update($folder, ['name' => $name]);
        return $updated->toArray();
    }

    public function deleteFolder(int $id): void
    {
        $spaceId = $this->requireSpaceId();
        $this->authz->requirePermission('manage_assets');

        $folder = $this->folders->find($spaceId, $id);
        if (!$folder) {
            throw new NotFoundApiException('Folder not found');
        }

        $hasSubfolders = count($this->folders->listByParent($spaceId, $id)) > 0;
        $hasFiles = count($this->media->listByFolder($spaceId, $id)) > 0;
        if ($hasSubfolders || $hasFiles) {
            throw new ValidationApiException('Folder is not empty', [
                'folder_id' => ['Folder must be empty before deletion'],
            ]);
        }

        $this->folders->delete($folder);
    }

    public function move(array $input): array
    {
        $this->authz->requirePermission('manage_assets');
        return $this->assets->move($input);
    }

    private function requireSpaceId(): int
    {
        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null || $spaceId <= 0) {
            throw new ValidationApiException('X-Space-Id header is required', [
                'space_id' => ['Missing X-Space-Id'],
            ]);
        }
        return $spaceId;
    }
}

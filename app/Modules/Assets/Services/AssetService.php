<?php

namespace App\Modules\Assets\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use App\Support\Exceptions\NotFoundApiException;
use App\Support\Exceptions\ValidationApiException;

use App\Modules\Assets\Services\Interfaces\AssetServiceInterface;
use App\Modules\Assets\Repositories\Interfaces\MediaRepositoryInterface;
use App\Modules\Assets\Repositories\Interfaces\MediaFolderRepositoryInterface;
use App\Modules\Assets\Validators\AssetValidator;
use App\Modules\Assets\Validators\FolderValidator;
use App\Modules\Assets\Validators\MoveValidator;

use App\Modules\Spaces\Repositories\Interfaces\SpaceRepositoryInterface;
use App\Modules\System\Authorization\Services\AuthorizationService;
use App\Modules\System\Audit\Services\Interfaces\AuditLogServiceInterface;

use App\Modules\Assets\Jobs\ExtractMediaMetadataJob;
use App\Modules\Assets\Jobs\GenerateThumbJob;
use App\Modules\Assets\Jobs\GenerateVideoPosterJob;
use App\Modules\Assets\Jobs\GenerateWebpJob;

final class AssetService implements AssetServiceInterface
{
    public function __construct(
        private readonly MediaRepositoryInterface $media,
        private readonly MediaFolderRepositoryInterface $folders,
        private readonly AuthorizationService $authz,
        private readonly AuditLogServiceInterface $audit,
        private readonly SpaceRepositoryInterface $spaces,
    ) {}

    public function list(array $params = []): array
    {
        $spaceId = $this->requireSpaceId();
        $this->authz->requirePermission('manage_assets');

        $folderId = array_key_exists('folder_id', $params) ? $params['folder_id'] : null;
        if ($folderId !== null && !is_int($folderId)) {
            $folderId = is_numeric($folderId) ? (int) $folderId : null;
        }

        $allowedLimits = [15, 30, 50, 100];
        $limit = $params['limit'] ?? 15;
        $limit = is_numeric($limit) ? (int) $limit : 15;
        if (!in_array($limit, $allowedLimits, true)) {
            $limit = 15;
        }

        $skip = $params['skip'] ?? 0;
        $skip = is_numeric($skip) ? (int) $skip : 0;
        if ($skip < 0) {
            $skip = 0;
        }

        $result = $this->media->listByFolderPaginated($spaceId, $folderId, $limit, $skip);
        $items = array_map(fn($m) => $m->toArray(), $result['items']);

        return ['items' => $items, 'total' => $result['total']];
    }

    public function upload(Request $request): array
    {
        $spaceId = $this->requireSpaceId();
        $this->authz->requirePermission('manage_assets');

        $validated = AssetValidator::validateUpload($request);
        $file = $validated['file'];
        $folderId = $validated['folder_id'];

        $space = $this->spaces->findOrFail($spaceId);
        $storagePrefix = (string)($space->storage_prefix ?? "space_{$spaceId}");

        $disk = config('cms_assets.disk', 'local');
        $baseDir = trim((string)config('cms_assets.base_dir', 'cms_media'), '/');

        $ext = $file->getClientOriginalExtension() ?: 'bin';
        $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $safeName = $safeName !== '' ? $safeName : 'file';

        $uuid = (string)Str::uuid();
        $relativePath = "{$baseDir}/{$storagePrefix}/original/{$uuid}.{$ext}";

        return DB::transaction(function () use ($spaceId, $disk, $relativePath, $file, $folderId, $safeName) {

            Storage::disk($disk)->put($relativePath, file_get_contents($file->getRealPath()));

            $size = Storage::disk($disk)->size($relativePath) ?: 0;
            $mime = $file->getClientMimeType();

            $kind = 'file';
            if ($mime && str_starts_with($mime, 'image/')) $kind = 'image';
            if ($mime && str_starts_with($mime, 'video/')) $kind = 'video';

            $checksum = sha1(Storage::disk($disk)->get($relativePath));

            $media = $this->media->create([
                'space_id' => $spaceId,
                'folder_id' => $folderId,
                'disk' => $disk,
                'path' => $relativePath,
                'filename' => $safeName . '.' . ($file->getClientOriginalExtension() ?: 'bin'),
                'mime' => $mime,
                'size' => $size,
                'checksum' => $checksum,
                'kind' => $kind,
                'meta' => [
                    'original_name' => $file->getClientOriginalName(),
                ],
                'created_by' => auth()->id(),
            ]);

            $this->audit->write(
                action: 'asset.upload',
                resource: 'assets',
                diff: ['after' => $media->toArray()],
                spaceId: $spaceId,
                actorId: auth()->id(),
            );

            Log::info('Asset uploaded', [
                'space_id' => $spaceId,
                'media_id' => $media->id,
                'disk' => $disk,
                'path' => $relativePath,
                'user_id' => auth()->id(),
            ]);

            dispatch(new ExtractMediaMetadataJob((int)$media->id));
            dispatch(new GenerateThumbJob((int)$media->id));
            dispatch(new GenerateWebpJob((int)$media->id));
            if ($kind === 'video') {
                dispatch(new GenerateVideoPosterJob((int)$media->id));
            }

            return $media->toArray();
        });
    }

    public function update(int $id, array $input): array
    {
        $spaceId = $this->requireSpaceId();
        $this->authz->requirePermission('manage_assets');

        $media = $this->media->find($spaceId, $id);
        if (!$media) throw new NotFoundApiException('Media not found');

        $validated = AssetValidator::validateUpdate($input);
        $before = $media->toArray();

        $updated = DB::transaction(function () use ($media, $validated) {
            return $this->media->update($media, $validated);
        });

        $this->audit->write(
            action: 'asset.update',
            resource: 'assets',
            diff: ['before' => $before, 'after' => $updated->toArray()],
            spaceId: $spaceId,
            actorId: auth()->id(),
        );

        return $updated->toArray();
    }

    public function delete(int $id): void
    {
        $spaceId = $this->requireSpaceId();
        $this->authz->requirePermission('manage_assets');

        $media = $this->media->find($spaceId, $id);
        if (!$media) throw new NotFoundApiException('Media not found');

        $before = $media->toArray();

        DB::transaction(function () use ($media) {
            $this->media->delete($media);
        });

        $this->audit->write(
            action: 'asset.delete',
            resource: 'assets',
            diff: ['before' => $before],
            spaceId: $spaceId,
            actorId: auth()->id(),
        );
    }

    public function listFolders(): array
    {
        $spaceId = $this->requireSpaceId();
        $this->authz->requirePermission('manage_assets');

        $folders = $this->folders->listAll($spaceId);
        return array_map(fn($folder) => $folder->toArray(), $folders);
    }

    public function createFolder(array $input): array
    {
        $spaceId = $this->requireSpaceId();
        $this->authz->requirePermission('manage_assets');

        $v = FolderValidator::validateCreate($input);

        $parent = null;
        $parentPath = '';
        if ($v['parent_id']) {
            $parent = $this->folders->find($spaceId, $v['parent_id']);
            if (!$parent) throw new NotFoundApiException('Parent folder not found');
            $parentPath = rtrim($parent->path, '/');
        }

        $path = $parentPath . '/' . trim($v['name'], '/');
        $path = '/' . trim($path, '/');

        $folder = DB::transaction(function () use ($spaceId, $v, $path) {
            return $this->folders->create([
                'space_id' => $spaceId,
                'parent_id' => $v['parent_id'],
                'name' => $v['name'],
                'path' => $path,
            ]);
        });

        $this->audit->write(
            action: 'asset.folder_create',
            resource: 'assets',
            diff: ['after' => $folder->toArray()],
            spaceId: $spaceId,
            actorId: auth()->id(),
        );

        return $folder->toArray();
    }

    public function move(array $input): array
    {
        $spaceId = $this->requireSpaceId();
        $this->authz->requirePermission('manage_assets');

        $v = MoveValidator::validate($input);

        if ($v['folder_id'] !== null) {
            $folder = $this->folders->find($spaceId, $v['folder_id']);
            if (!$folder) throw new NotFoundApiException('Folder not found');
        }

        $updatedIds = [];

        DB::transaction(function () use ($spaceId, $v, &$updatedIds) {
            foreach ($v['ids'] as $id) {
                $m = $this->media->find($spaceId, $id);
                if (!$m) continue;
                $this->media->update($m, ['folder_id' => $v['folder_id']]);
                $updatedIds[] = $id;
            }
        });

        $this->audit->write(
            action: 'asset.move',
            resource: 'assets',
            diff: ['ids' => $updatedIds, 'folder_id' => $v['folder_id']],
            spaceId: $spaceId,
            actorId: auth()->id(),
        );

        return ['moved' => $updatedIds, 'folder_id' => $v['folder_id']];
    }

    public function createMediaFromPath(string $tempPath, string $originalFilename, ?int $folderId, int $spaceId): array
    {
        $this->authz->requirePermission('manage_assets');

        $space = $this->spaces->findOrFail($spaceId);
        $storagePrefix = (string) ($space->storage_prefix ?? "space_{$spaceId}");
        $disk = config('cms_assets.disk', 'local');
        $baseDir = trim((string) config('cms_assets.base_dir', 'cms_media'), '/');

        $ext = pathinfo($originalFilename, PATHINFO_EXTENSION) ?: 'bin';
        $safeName = \Illuminate\Support\Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME));
        $safeName = $safeName !== '' ? $safeName : 'file';

        $uuid = (string) \Illuminate\Support\Str::uuid();
        $relativePath = "{$baseDir}/{$storagePrefix}/original/{$uuid}.{$ext}";

        $mime = \Illuminate\Support\Facades\File::mimeType($tempPath) ?: 'application/octet-stream';
        $kind = 'file';
        if ($mime && str_starts_with($mime, 'image/')) $kind = 'image';
        if ($mime && str_starts_with($mime, 'video/')) $kind = 'video';

        return DB::transaction(function () use ($tempPath, $spaceId, $disk, $relativePath, $folderId, $safeName, $originalFilename, $ext, $mime, $kind) {
            Storage::disk($disk)->put($relativePath, file_get_contents($tempPath));
            $size = Storage::disk($disk)->size($relativePath) ?: 0;
            $checksum = sha1(Storage::disk($disk)->get($relativePath));

            $media = $this->media->create([
                'space_id' => $spaceId,
                'folder_id' => $folderId,
                'disk' => $disk,
                'path' => $relativePath,
                'filename' => $safeName . '.' . $ext,
                'mime' => $mime,
                'size' => $size,
                'checksum' => $checksum,
                'kind' => $kind,
                'meta' => ['original_name' => $originalFilename],
                'created_by' => auth()->id(),
            ]);

            $this->audit->write(
                action: 'asset.upload',
                resource: 'assets',
                diff: ['after' => $media->toArray()],
                spaceId: $spaceId,
                actorId: auth()->id(),
            );

            Log::info('Asset uploaded (chunked)', [
                'space_id' => $spaceId,
                'media_id' => $media->id,
                'user_id' => auth()->id(),
            ]);

            dispatch(new ExtractMediaMetadataJob((int) $media->id));
            dispatch(new GenerateThumbJob((int) $media->id));
            dispatch(new GenerateWebpJob((int) $media->id));
            if ($kind === 'video') {
                dispatch(new GenerateVideoPosterJob((int) $media->id));
            }

            return $media->toArray();
        });
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

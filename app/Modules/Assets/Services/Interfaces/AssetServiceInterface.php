<?php

namespace App\Modules\Assets\Services\Interfaces;

use Illuminate\Http\Request;

interface AssetServiceInterface
{
    /**
     * Paginated list for assets index. Params: folder_id (int|null), limit (int), skip (int).
     * Returns ['items' => array (full asset documents), 'total' => int].
     */
    public function list(array $params = []): array;
    public function upload(Request $request): array;
    public function update(int $id, array $input): array;
    public function delete(int $id): void;

    public function createFolder(array $input): array;
    public function move(array $input): array;

    public function createMediaFromPath(string $tempPath, string $originalFilename, ?int $folderId, int $spaceId): array;
}

<?php

namespace App\Modules\Assets\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use App\Support\Exceptions\ValidationApiException;
use App\Modules\Assets\Services\Interfaces\AssetServiceInterface;
use App\Modules\System\Realtime\Events\AssetProgressRealtimeEvent;

final class ChunkedUploadService
{
    private const META_KEY = 'chunk_upload:meta:';
    private const TTL_MINUTES = 60;

    public function __construct(
        private readonly AssetServiceInterface $assets,
    ) {}

    public function init(Request $request): array
    {
        $request->validate([
            'filename' => 'required|string|max:255',
            'total_chunks' => 'required|integer|min:1|max:10000',
            'folder_id' => 'nullable|integer',
        ]);

        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null || $spaceId <= 0) {
            throw new ValidationApiException('Validation failed', ['space_id' => ['X-Space-Id required']]);
        }

        $uploadId = (string) Str::uuid();
        $meta = [
            'filename' => $request->input('filename'),
            'total_chunks' => (int) $request->input('total_chunks'),
            'folder_id' => $request->has('folder_id') ? (int) $request->input('folder_id') : null,
            'space_id' => $spaceId,
            'received' => [],
        ];

        Cache::put(self::META_KEY . $uploadId, $meta, now()->addMinutes(self::TTL_MINUTES));

        return ['upload_id' => $uploadId];
    }

    public function chunk(Request $request): array
    {
        $request->validate([
            'upload_id' => 'required|uuid',
            'chunk_index' => 'required|integer|min:0',
            'file' => 'required|file',
        ]);

        $uploadId = $request->input('upload_id');
        $chunkIndex = (int) $request->input('chunk_index');
        $file = $request->file('file');

        $meta = Cache::get(self::META_KEY . $uploadId);
        if (!$meta) {
            throw new ValidationApiException('Validation failed', ['upload_id' => ['Invalid or expired upload']]);
        }

        $totalChunks = $meta['total_chunks'];
        if ($chunkIndex >= $totalChunks) {
            throw new ValidationApiException('Validation failed', ['chunk_index' => ['Out of range']]);
        }

        $chunkDir = $this->chunkDir($uploadId);
        $chunkPath = $chunkDir . '/chunk_' . $chunkIndex;
        Storage::disk('local')->put($chunkPath, file_get_contents($file->getRealPath()));

        $meta['received'][$chunkIndex] = true;
        Cache::put(self::META_KEY . $uploadId, $meta, now()->addMinutes(self::TTL_MINUTES));

        return [
            'upload_id' => $uploadId,
            'chunk_index' => $chunkIndex,
            'received' => count($meta['received']),
            'total_chunks' => $totalChunks,
        ];
    }

    public function complete(Request $request): array
    {
        $request->validate([
            'upload_id' => 'required|uuid',
        ]);

        $uploadId = $request->input('upload_id');
        $meta = Cache::get(self::META_KEY . $uploadId);
        if (!$meta) {
            throw new ValidationApiException('Validation failed', ['upload_id' => ['Invalid or expired upload']]);
        }

        $totalChunks = $meta['total_chunks'];
        $chunkDir = $this->chunkDir($uploadId);

        for ($i = 0; $i < $totalChunks; $i++) {
            $path = $chunkDir . '/chunk_' . $i;
            if (!Storage::disk('local')->exists($path)) {
                throw new ValidationApiException('Validation failed', [
                    'upload_id' => ['Missing chunk ' . $i . '. Upload all chunks before complete.'],
                ]);
            }
        }

        $mergedPath = storage_path('app/' . $chunkDir . '/merged');
        $fs = Storage::disk('local');
        $fullChunkDir = storage_path('app/' . $chunkDir);

        $merged = fopen($mergedPath, 'wb');
        if (!$merged) {
            throw new \RuntimeException('Cannot create merged file');
        }

        try {
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $fullChunkDir . '/chunk_' . $i;
                $data = file_get_contents($chunkPath);
                fwrite($merged, $data);
            }
        } finally {
            fclose($merged);
        }

        try {
            $media = $this->assets->createMediaFromPath(
                $mergedPath,
                $meta['filename'],
                $meta['folder_id'],
                $meta['space_id'],
            );
        } finally {
            $this->cleanupChunks($chunkDir);
        }

        Cache::forget(self::META_KEY . $uploadId);

        $spaceId = (int) $meta['space_id'];
        $assetId = (int) ($media['id'] ?? 0);
        if ($assetId > 0) {
            broadcast(new AssetProgressRealtimeEvent($spaceId, $assetId, 100, 'done', $uploadId));
        }

        return $media;
    }

    private function chunkDir(string $uploadId): string
    {
        $base = config('cms_assets.chunks.dir', 'cms_chunks');
        return trim($base, '/') . '/' . $uploadId;
    }

    private function cleanupChunks(string $chunkDir): void
    {
        $fullDir = storage_path('app/' . $chunkDir);
        if (!is_dir($fullDir)) {
            return;
        }
        foreach (glob($fullDir . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($fullDir);
    }
}

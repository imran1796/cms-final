<?php

namespace App\Modules\Assets\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

use App\Support\Exceptions\NotFoundApiException;
use App\Modules\Assets\Services\Interfaces\AssetPublicServiceInterface;
use App\Modules\Assets\Services\Interfaces\ImageTransformServiceInterface;
use App\Modules\Assets\Repositories\Interfaces\MediaRepositoryInterface;
use App\Modules\Assets\Repositories\Interfaces\MediaVariantRepositoryInterface;
use App\Modules\Assets\Validators\AssetTransformValidator;

final class AssetPublicService implements AssetPublicServiceInterface
{
    public function __construct(
        private readonly MediaRepositoryInterface $media,
        private readonly MediaVariantRepositoryInterface $variants,
        private readonly ImageTransformServiceInterface $transform,
    ) {}

    public function streamOriginal(int $id): StreamedResponse
    {
        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null || $spaceId <= 0) {
            throw new NotFoundApiException('Resource not found');
        }

        $m = $this->media->findForSpace($spaceId, $id);
        if (!$m) throw new NotFoundApiException('Resource not found');

        return $this->streamFile($m->disk, $m->path, $m->mime ?: 'application/octet-stream');
    }

    public function streamImage(int $id, array $query): StreamedResponse
    {
        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null || $spaceId <= 0) {
            throw new NotFoundApiException('Resource not found');
        }

        $m = $this->media->findForSpace($spaceId, $id);
        if (!$m) throw new NotFoundApiException('Resource not found');

        if (!$this->isImage($m)) {
            throw new NotFoundApiException('Resource not found');
        }

        $transform = AssetTransformValidator::normalizeQuery($query);
        $key = AssetTransformValidator::transformKey(null, $transform);

        $existing = $this->variants->findByKey((int)$m->id, $key);
        if ($existing) {
            return $this->streamFile($existing->disk, $existing->path, $existing->mime ?: ($m->mime ?: 'application/octet-stream'));
        }

        $result = $this->createVariant($m, $transform, null, $key);
        return $this->streamFile($result['disk'], $result['path'], $result['mime']);
    }

    public function streamPreset(int $id, string $presetKey): StreamedResponse
    {
        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null || $spaceId <= 0) {
            throw new NotFoundApiException('Resource not found');
        }

        $m = $this->media->findForSpace($spaceId, $id);
        if (!$m) throw new NotFoundApiException('Resource not found');

        $presets = (array) config('cms_assets.presets', []);
        $preset = $presets[$presetKey] ?? null;
        if (!$preset) throw new NotFoundApiException('Resource not found');

        $isPoster = $presetKey === 'poster';
        if ($isPoster) {
            if (!$this->isVideo($m)) {
                throw new NotFoundApiException('Resource not found');
            }
        } else {
            if (!$this->isImage($m)) {
                throw new NotFoundApiException('Resource not found');
            }
        }

        $transform = AssetTransformValidator::normalizeQuery($preset);
        $key = AssetTransformValidator::transformKey($presetKey, $transform);

        $existing = $this->variants->findByKey((int)$m->id, $key);
        if ($existing) {
            return $this->streamFile($existing->disk, $existing->path, $existing->mime ?: ($m->mime ?: 'application/octet-stream'));
        }

        if ($isPoster) {
            throw new NotFoundApiException('Resource not found');
        }

        $result = $this->createVariant($m, $transform, $presetKey, $key);
        return $this->streamFile($result['disk'], $result['path'], $result['mime']);
    }

    private function createVariant(object $m, array $transform, ?string $presetKey, string $key): array
    {
        $absolutePath = $this->resolveAbsolutePath($m->disk, $m->path);

        try {
            $out = $this->transform->transform($absolutePath, $transform);
        } catch (\Throwable $e) {
            Log::warning('Image transform failed', ['media_id' => $m->id, 'message' => $e->getMessage()]);
            throw new NotFoundApiException('Resource not found');
        }

        $ext = $out['ext'];
        $variantPath = $this->makeVariantPath($m->path, $key, $ext);

        Storage::disk($m->disk)->put($variantPath, $out['bytes']);
        $size = strlen($out['bytes']);

        $this->variants->create([
            'media_id' => (int) $m->id,
            'preset_key' => $presetKey,
            'transform_key' => $key,
            'transform' => $transform,
            'disk' => $m->disk,
            'path' => $variantPath,
            'mime' => $out['mime'],
            'size' => $size,
            'width' => $out['width'] ?? null,
            'height' => $out['height'] ?? null,
            'meta' => ['generated_by' => 'intervention'],
        ]);

        Log::info('Variant generated', ['media_id' => $m->id, 'key' => $key]);

        return [
            'disk' => $m->disk,
            'path' => $variantPath,
            'mime' => $out['mime'],
        ];
    }

    private function isImage(object $m): bool
    {
        $kind = $m->kind ?? '';
        $mime = (string) ($m->mime ?? '');

        return $kind === 'image' || str_starts_with($mime, 'image/');
    }

    private function isVideo(object $m): bool
    {
        $kind = $m->kind ?? '';
        $mime = (string) ($m->mime ?? '');

        return $kind === 'video' || str_starts_with($mime, 'video/');
    }

    private function resolveAbsolutePath(string $disk, string $path): string
    {
        if ($disk !== 'local') {
            throw new \RuntimeException('Image transforms require local disk');
        }

        return Storage::disk($disk)->path($path);
    }

    private function makeVariantPath(string $originalPath, string $key, string $ext): string
    {
        $dir = preg_replace('#/original/[^/]+$#', '/variants', $originalPath);
        if (!$dir || $dir === $originalPath) {
            $dir = trim($originalPath, '/') . '/variants';
        }
        $dir = trim($dir, '/');

        return $dir . '/' . $key . '.' . $ext;
    }

    private function streamFile(string $disk, string $path, string $mime): StreamedResponse
    {
        if (!Storage::disk($disk)->exists($path)) {
            throw new NotFoundApiException('Resource not found');
        }

        return new StreamedResponse(function () use ($disk, $path) {
            $stream = Storage::disk($disk)->readStream($path);
            if ($stream === false) {
                throw new NotFoundApiException('Resource not found');
            }

            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}

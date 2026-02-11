<?php

namespace App\Modules\Assets\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

use App\Modules\Assets\Models\Media;
use App\Modules\Assets\Repositories\Interfaces\MediaVariantRepositoryInterface;
use App\Modules\Assets\Services\Interfaces\ImageTransformServiceInterface;
use App\Modules\Assets\Validators\AssetTransformValidator;
use App\Modules\System\Realtime\Events\AssetProgressRealtimeEvent;

final class GenerateWebpJob implements ShouldQueue
{
    use Queueable;

    private const WEBP_PARAMS = [
        'w' => null,
        'h' => null,
        'fit' => 'contain',
        'q' => 82,
        'format' => 'webp',
    ];

    public function __construct(public int $mediaId) {}

    public function handle(
        ImageTransformServiceInterface $transform,
        MediaVariantRepositoryInterface $variants,
    ): void {
        $media = Media::query()->find($this->mediaId);
        if (!$media) {
            return;
        }

        $spaceId = (int) $media->space_id;
        broadcast(new AssetProgressRealtimeEvent($spaceId, $this->mediaId, 0, 'encoding'));

        if (!str_starts_with((string) ($media->mime ?? ''), 'image/')) {
            broadcast(new AssetProgressRealtimeEvent($spaceId, $this->mediaId, 100, 'done'));
            return;
        }

        $disk = $media->disk ?? config('cms_assets.disk', 'local');
        if ($disk !== 'local') {
            broadcast(new AssetProgressRealtimeEvent($spaceId, $this->mediaId, 100, 'done'));
            return;
        }

        $transformParams = AssetTransformValidator::normalizeQuery(self::WEBP_PARAMS);
        $key = AssetTransformValidator::transformKey('webp', $transformParams);

        if ($variants->findByKey($this->mediaId, $key)) {
            broadcast(new AssetProgressRealtimeEvent($spaceId, $this->mediaId, 100, 'done'));
            return;
        }

        try {
            $path = Storage::disk($disk)->path($media->path);
            $out = $transform->transform($path, $transformParams);

            $variantPath = $this->makeVariantPath($media->path, $key, $out['ext']);
            Storage::disk($disk)->put($variantPath, $out['bytes']);

            $variants->create([
                'media_id' => $this->mediaId,
                'preset_key' => null,
                'transform_key' => $key,
                'transform' => $transformParams,
                'disk' => $disk,
                'path' => $variantPath,
                'mime' => $out['mime'],
                'size' => strlen($out['bytes']),
                'width' => $out['width'] ?? null,
                'height' => $out['height'] ?? null,
                'meta' => ['generated_by' => 'GenerateWebpJob'],
            ]);

            Log::info('WebP generated', ['media_id' => $this->mediaId]);
            broadcast(new AssetProgressRealtimeEvent($spaceId, $this->mediaId, 100, 'done'));
        } catch (\Throwable $e) {
            Log::warning('GenerateWebp failed', ['media_id' => $this->mediaId, 'message' => $e->getMessage()]);
            broadcast(new AssetProgressRealtimeEvent($spaceId, $this->mediaId, 100, 'done'));
        }
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
}

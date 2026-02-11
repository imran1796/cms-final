<?php

namespace App\Modules\Assets\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

use App\Modules\Assets\Models\Media;
use App\Modules\Assets\Repositories\Interfaces\MediaVariantRepositoryInterface;
use App\Modules\Assets\Services\Interfaces\VideoMetadataServiceInterface;
use App\Modules\Assets\Validators\AssetTransformValidator;
use App\Modules\System\Realtime\Events\AssetProgressRealtimeEvent;

final class GenerateVideoPosterJob implements ShouldQueue
{
    use Queueable;

    private const POSTER_PRESET = [
        'format' => 'jpg',
    ];

    public function __construct(public int $mediaId) {}

    public function handle(
        VideoMetadataServiceInterface $videoMetadata,
        MediaVariantRepositoryInterface $variants,
    ): void {
        $media = Media::query()->find($this->mediaId);
        if (!$media) {
            return;
        }

        $spaceId = (int) $media->space_id;
        broadcast(new AssetProgressRealtimeEvent($spaceId, $this->mediaId, 0, 'encoding'));

        if (!str_starts_with((string) ($media->mime ?? ''), 'video/')) {
            broadcast(new AssetProgressRealtimeEvent($spaceId, $this->mediaId, 100, 'done'));
            return;
        }

        $disk = $media->disk ?? config('cms_assets.disk', 'local');
        if ($disk !== 'local') {
            broadcast(new AssetProgressRealtimeEvent($spaceId, $this->mediaId, 100, 'done'));
            return;
        }

        $transformParams = AssetTransformValidator::normalizeQuery(self::POSTER_PRESET);
        $key = AssetTransformValidator::transformKey('poster', $transformParams);

        if ($variants->findByKey($this->mediaId, $key)) {
            broadcast(new AssetProgressRealtimeEvent($spaceId, $this->mediaId, 100, 'done'));
            return;
        }

        try {
            $videoPath = Storage::disk($disk)->path($media->path);
            $variantPath = $this->makeVariantPath($media->path, $key, 'jpg');
            $outputPath = Storage::disk($disk)->path($variantPath);
            $variantDir = \dirname($variantPath);
            $fullDir = Storage::disk($disk)->path($variantDir);
            if (!is_dir($fullDir)) {
                mkdir($fullDir, 0755, true);
            }

            $videoMetadata->extractFrame($videoPath, $outputPath, 1.0);

            $size = Storage::disk($disk)->exists($variantPath)
                ? (int) Storage::disk($disk)->size($variantPath)
                : 0;

            $variants->create([
                'media_id' => $this->mediaId,
                'preset_key' => 'poster',
                'transform_key' => $key,
                'transform' => $transformParams,
                'disk' => $disk,
                'path' => $variantPath,
                'mime' => 'image/jpeg',
                'size' => $size,
                'width' => $media->width,
                'height' => $media->height,
                'meta' => ['generated_by' => 'GenerateVideoPosterJob'],
            ]);

            Log::info('Video poster generated', ['media_id' => $this->mediaId]);
            broadcast(new AssetProgressRealtimeEvent($spaceId, $this->mediaId, 100, 'done'));
        } catch (\Throwable $e) {
            Log::warning('GenerateVideoPoster failed', ['media_id' => $this->mediaId, 'message' => $e->getMessage()]);
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

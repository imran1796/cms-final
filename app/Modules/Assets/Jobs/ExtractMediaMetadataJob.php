<?php

namespace App\Modules\Assets\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

use App\Modules\Assets\Models\Media;
use App\Modules\Assets\Services\Interfaces\ImageTransformServiceInterface;
use App\Modules\Assets\Services\Interfaces\VideoMetadataServiceInterface;

final class ExtractMediaMetadataJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $mediaId) {}

    public function handle(
        ImageTransformServiceInterface $transform,
        VideoMetadataServiceInterface $videoMetadata,
    ): void {
        $media = Media::query()->find($this->mediaId);
        if (!$media) {
            return;
        }

        $disk = $media->disk ?? config('cms_assets.disk', 'local');
        if ($disk !== 'local') {
            return;
        }

        $path = Storage::disk($disk)->path($media->path);
        $mime = (string) ($media->mime ?? '');

        try {
            if (str_starts_with($mime, 'image/')) {
                $dims = $transform->getDimensions($path);
                $media->update([
                    'width' => $dims['width'],
                    'height' => $dims['height'],
                ]);
            } elseif (str_starts_with($mime, 'video/')) {
                $meta = $videoMetadata->getMetadata($path);
                $media->update([
                    'width' => $meta['width'],
                    'height' => $meta['height'],
                    'duration' => $meta['duration'],
                ]);
            } else {
                return;
            }

            Log::info('Media metadata extracted', ['media_id' => $this->mediaId]);
        } catch (\Throwable $e) {
            Log::warning('ExtractMediaMetadata failed', ['media_id' => $this->mediaId, 'message' => $e->getMessage()]);
        }
    }
}

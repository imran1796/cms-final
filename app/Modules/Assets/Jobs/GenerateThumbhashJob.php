<?php

namespace App\Modules\Assets\Jobs;

use App\Modules\Assets\Models\Media;
use App\Modules\Assets\Services\ThumbhashGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class GenerateThumbhashJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $mediaId) {}

    public function handle(): void
    {
        $media = Media::query()->find($this->mediaId);
        if (!$media || !str_starts_with((string) ($media->mime ?? ''), 'image/')) {
            return;
        }

        $disk = $media->disk ?? config('cms_assets.disk', 'local');
        $path = Storage::disk($disk)->path($media->path);
        $thumbhash = ThumbhashGenerator::generate($path);

        if ($thumbhash === null) {
            Log::debug('Thumbhash could not be generated', ['media_id' => $this->mediaId]);
            return;
        }

        $meta = $media->meta ?? [];
        $meta['thumbhash'] = $thumbhash;
        $media->update(['meta' => $meta]);
    }
}

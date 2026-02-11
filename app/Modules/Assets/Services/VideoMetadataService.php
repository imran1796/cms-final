<?php

namespace App\Modules\Assets\Services;

use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use App\Modules\Assets\Services\Interfaces\VideoMetadataServiceInterface;

final class VideoMetadataService implements VideoMetadataServiceInterface
{
    private ?FFMpeg $ffmpeg = null;
    private ?FFProbe $ffprobe = null;

    private function ffprobe(): FFProbe
    {
        if ($this->ffprobe === null) {
            $config = config('cms_assets.ffmpeg', []);
            $this->ffprobe = FFProbe::create($config);
        }

        return $this->ffprobe;
    }

    private function ffmpeg(): FFMpeg
    {
        if ($this->ffmpeg === null) {
            $config = config('cms_assets.ffmpeg', []);
            $this->ffmpeg = FFMpeg::create($config);
        }

        return $this->ffmpeg;
    }

    public function getMetadata(string $absolutePath): array
    {
        $probe = $this->ffprobe();
        $format = $probe->format($absolutePath);
        $duration = (float) ($format->get('duration') ?? 0);
        $durationSeconds = (int) round($duration);

        $streams = $probe->streams($absolutePath);
        $videos = $streams->videos();
        $video = $videos->first();

        $width = 0;
        $height = 0;
        if ($video) {
            $width = (int) ($video->get('width') ?? 0);
            $height = (int) ($video->get('height') ?? 0);
        }

        return [
            'width' => $width,
            'height' => $height,
            'duration' => $durationSeconds,
        ];
    }

    public function extractFrame(string $videoPath, string $outputPath, float $seconds = 1.0): void
    {
        $metadata = $this->getMetadata($videoPath);
        $duration = $metadata['duration'];
        $at = $duration > 0 && $seconds >= $duration
            ? max(0, $duration - 0.5)
            : max(0, $seconds);

        $video = $this->ffmpeg()->open($videoPath);
        $video->frame(TimeCode::fromSeconds($at))->save($outputPath, false);
    }
}

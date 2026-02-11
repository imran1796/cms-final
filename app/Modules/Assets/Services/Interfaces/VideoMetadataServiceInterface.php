<?php

namespace App\Modules\Assets\Services\Interfaces;

interface VideoMetadataServiceInterface
{
    public function getMetadata(string $absolutePath): array;

    public function extractFrame(string $videoPath, string $outputPath, float $seconds = 1.0): void;
}

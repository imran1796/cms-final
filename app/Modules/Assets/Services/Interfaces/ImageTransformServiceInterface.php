<?php

namespace App\Modules\Assets\Services\Interfaces;

interface ImageTransformServiceInterface
{
    public function transform(string $absolutePath, array $params): array;

    public function getDimensions(string $absolutePath): array;
}

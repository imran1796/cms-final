<?php

namespace App\Modules\Assets\Services\Interfaces;

use Symfony\Component\HttpFoundation\StreamedResponse;

interface AssetPublicServiceInterface
{
    public function streamOriginal(int $id): StreamedResponse;
    public function streamImage(int $id, array $query): StreamedResponse;
    public function streamPreset(int $id, string $presetKey): StreamedResponse;
    
}

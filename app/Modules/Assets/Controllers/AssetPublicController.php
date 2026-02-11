<?php

namespace App\Modules\Assets\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assets\Services\Interfaces\AssetPublicServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class AssetPublicController extends Controller
{
    public function __construct(private readonly AssetPublicServiceInterface $service) {}

    public function original(int $id)
    {
        try {
            return $this->service->streamOriginal($id);
        } catch (\Throwable $e) {
            Log::error('Public original failed', ['id' => $id, 'message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function image(Request $request, int $id)
    {
        try {
            return $this->service->streamImage($id, $request->query());
        } catch (\Throwable $e) {
            Log::error('Public image failed', ['id' => $id, 'message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function preset(int $id, string $presetKey)
    {
        try {
            return $this->service->streamPreset($id, $presetKey);
        } catch (\Throwable $e) {
            Log::error('Public preset failed', ['id' => $id, 'preset' => $presetKey, 'message' => $e->getMessage()]);
            throw $e;
        }
    }
}

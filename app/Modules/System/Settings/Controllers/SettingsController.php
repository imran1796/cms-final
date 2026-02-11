<?php

namespace App\Modules\System\Settings\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\Settings\Services\SettingsService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

final class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $service
    ) {
    }

    public function index(): \Illuminate\Http\JsonResponse
    {
        $settings = $this->service->getAll();
        return ApiResponse::success($settings, 'Settings');
    }

    public function update(Request $request): \Illuminate\Http\JsonResponse
    {
        $payload = $request->all();
        if (!is_array($payload)) {
            $payload = [];
        }
        $settings = $this->service->update($payload);
        return ApiResponse::success($settings, 'Settings updated');
    }
}

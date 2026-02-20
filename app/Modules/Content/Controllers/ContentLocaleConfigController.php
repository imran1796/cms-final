<?php

namespace App\Modules\Content\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Content\Services\ContentLocaleConfigService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ContentLocaleConfigController extends Controller
{
    public function __construct(
        private readonly ContentLocaleConfigService $service
    ) {
    }

    public function show(): JsonResponse
    {
        return ApiResponse::success($this->service->get(), 'Content locale config');
    }
}

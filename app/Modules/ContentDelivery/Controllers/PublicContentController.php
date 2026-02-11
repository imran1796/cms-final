<?php

namespace App\Modules\ContentDelivery\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ContentDelivery\Services\Interfaces\PublicContentServiceInterface;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class PublicContentController extends Controller
{
    public function __construct(
        private readonly PublicContentServiceInterface $service
    ) {
    }

    public function index(Request $request, string $collectionHandle)
    {
        try {
            $result = $this->service->list($collectionHandle, $request->query());

            return ApiResponse::success($result, 'Content list');
        } catch (\Throwable $e) {
            Log::error('Public content list failed', [
                'handle' => $collectionHandle,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function show(Request $request, string $collectionHandle, int $id)
    {
        try {
            $result = $this->service->get($collectionHandle, $id, $request->query());

            return ApiResponse::success($result, 'Content item');
        } catch (\Throwable $e) {
            Log::error('Public content get failed', [
                'handle' => $collectionHandle,
                'id' => $id,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

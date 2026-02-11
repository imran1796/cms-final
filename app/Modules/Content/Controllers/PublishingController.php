<?php

namespace App\Modules\Content\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Content\Services\PublishingServiceInterface;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Log;

final class PublishingController extends Controller
{
    public function __construct(private readonly PublishingServiceInterface $service) {}

    public function publish(string $collectionHandle, int $id)
    {
        try {
            $entry = $this->service->publish($collectionHandle, $id);
            return ApiResponse::success($entry, 'Entry published');
        } catch (\Throwable $e) {
            Log::error('PublishingController@publish failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function unpublish(string $collectionHandle, int $id)
    {
        try {
            $entry = $this->service->unpublish($collectionHandle, $id);
            return ApiResponse::success($entry, 'Entry unpublished');
        } catch (\Throwable $e) {
            Log::error('PublishingController@unpublish failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }
}

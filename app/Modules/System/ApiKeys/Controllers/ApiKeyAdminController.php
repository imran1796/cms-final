<?php

namespace App\Modules\System\ApiKeys\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\ApiKeys\Services\Interfaces\ApiKeyServiceInterface;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class ApiKeyAdminController extends Controller
{
    public function __construct(
        private readonly ApiKeyServiceInterface $service
    ) {
    }

    public function index()
    {
        return ApiResponse::success($this->service->list(), 'API keys');
    }

    public function store(Request $request)
    {
        try {
            $res = $this->service->create($request->all());

            return ApiResponse::created([
                'api_key' => $res['api_key'],
                'plain_token' => $res['plain_token'],
            ], 'API key created');
        } catch (\Throwable $e) {
            Log::error('ApiKeyAdminController@store failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(int $id, Request $request)
    {
        try {
            $apiKey = $this->service->update($id, $request->all());
            return ApiResponse::success($apiKey, 'API key updated');
        } catch (\Throwable $e) {
            Log::error('ApiKeyAdminController@update failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->service->delete($id);
            return ApiResponse::success(null, 'API key deleted');
        } catch (\Throwable $e) {
            Log::error('ApiKeyAdminController@destroy failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function regenerate(int $id)
    {
        try {
            $res = $this->service->regenerate($id);

            return ApiResponse::success([
                'api_key' => $res['api_key'],
                'plain_token' => $res['plain_token'], // returned ONCE
            ], 'API key regenerated');
        } catch (\Throwable $e) {
            Log::error('ApiKeyAdminController@regenerate failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }
}

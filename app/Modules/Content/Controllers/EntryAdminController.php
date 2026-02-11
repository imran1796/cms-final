<?php

namespace App\Modules\Content\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Content\Services\EntryServiceInterface;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class EntryAdminController extends Controller
{
    public function __construct(private readonly EntryServiceInterface $service)
    {
    }

    public function index(string $collectionHandle, Request $request)
    {
        return ApiResponse::success(
            $this->service->list($collectionHandle, $request->query()),
            'Entries'
        );
    }

    public function store(string $collectionHandle, Request $request)
    {
        try {
            $entry = $this->service->create($collectionHandle, $request->all());
            return ApiResponse::created($entry, 'Entry created');
        } catch (\Throwable $e) {
            Log::error('EntryAdminController@store failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function show(string $collectionHandle, int $id, Request $request)
    {
        $payload = $this->service->get($collectionHandle, $id, $request->query());
        return ApiResponse::success($payload, 'Entry');
    }

    public function update(string $collectionHandle, int $id, Request $request)
    {
        try {
            $entry = $this->service->update($collectionHandle, $id, $request->all());
            return ApiResponse::success($entry, 'Entry updated');
        } catch (\Throwable $e) {
            Log::error('EntryAdminController@update failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function destroy(string $collectionHandle, int $id)
    {
        try {
            $this->service->delete($collectionHandle, $id);
            return ApiResponse::success(null, 'Entry deleted');
        } catch (\Throwable $e) {
            Log::error('EntryAdminController@destroy failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }
}

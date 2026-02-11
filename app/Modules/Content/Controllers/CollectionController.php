<?php

namespace App\Modules\Content\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Content\Services\ContentTypeServiceInterface;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class CollectionController extends Controller
{
    public function __construct(private readonly ContentTypeServiceInterface $service)
    {
    }

    public function index()
    {
        return ApiResponse::success($this->service->list(), 'Collections');
    }

    public function store(Request $request)
    {
        try {
            $collection = $this->service->create($request->all());
            return ApiResponse::created($collection, 'Collection created');
        } catch (\Throwable $e) {
            Log::error('CollectionController@store failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function show(int $id)
    {
        return ApiResponse::success($this->service->get($id), 'Collection');
    }

    public function update(int $id, Request $request)
    {
        try {
            $collection = $this->service->update($id, $request->all());
            return ApiResponse::success($collection, 'Collection updated');
        } catch (\Throwable $e) {
            Log::error('CollectionController@update failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->service->delete($id);
            return ApiResponse::success(null, 'Collection deleted');
        } catch (\Throwable $e) {
            Log::error('CollectionController@destroy failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function addField(int $id, Request $request)
    {
        try {
            $collection = $this->service->addField($id, $request->all());
            return ApiResponse::success($collection, 'Field added');
        } catch (\Throwable $e) {
            Log::error('CollectionController@addField failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function updateField(int $id, string $fieldId, Request $request)
    {
        try {
            $collection = $this->service->updateField($id, $fieldId, $request->all());
            return ApiResponse::success($collection, 'Field updated');
        } catch (\Throwable $e) {
            Log::error('CollectionController@updateField failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function deleteField(int $id, string $fieldId)
    {
        try {
            $collection = $this->service->deleteField($id, $fieldId);
            return ApiResponse::success($collection, 'Field deleted');
        } catch (\Throwable $e) {
            Log::error('CollectionController@deleteField failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }
}

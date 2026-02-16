<?php

namespace App\Modules\Assets\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Modules\Assets\Services\Interfaces\AssetServiceInterface;
use App\Modules\Assets\Services\ChunkedUploadService;

final class AssetAdminController extends Controller
{
    public function __construct(
        private readonly AssetServiceInterface $service,
        private readonly ChunkedUploadService $chunked,
    ) {}

    public function index(Request $request)
    {
        try {
            $folderId = $request->query('folder_id');
            if ($folderId !== null && $folderId !== '') {
                $folderId = is_numeric($folderId) ? (int) $folderId : null;
            } else {
                $folderId = null;
            }

            $allowedLimits = [15, 30, 50, 100];
            $limit = $request->query('limit', 15);
            $limit = is_numeric($limit) ? (int) $limit : 15;
            if (!in_array($limit, $allowedLimits, true)) {
                $limit = 15;
            }

            $skip = $request->query('skip', 0);
            $skip = is_numeric($skip) ? (int) $skip : 0;
            if ($skip < 0) {
                $skip = 0;
            }

            $result = $this->service->list([
                'folder_id' => $folderId,
                'limit' => $limit,
                'skip' => $skip,
            ]);

            return ApiResponse::success(
                $result['items'],
                'Assets list',
                200,
                ['total' => $result['total'], 'limit' => $limit, 'skip' => $skip]
            );
        } catch (\Throwable $e) {
            Log::error('Assets index failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function store(Request $request)
    {
        try {
            $media = $this->service->upload($request);
            return ApiResponse::created($media, 'Asset uploaded');
        } catch (\Throwable $e) {
            Log::error('Assets upload failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $media = $this->service->update($id, $request->all());
            return ApiResponse::success($media, 'Asset updated');
        } catch (\Throwable $e) {
            Log::error('Assets update failed', ['id' => $id, 'message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->service->delete($id);
            return ApiResponse::success([], 'Asset deleted');
        } catch (\Throwable $e) {
            Log::error('Assets delete failed', ['id' => $id, 'message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function createFolder(Request $request)
    {
        try {
            $folder = $this->service->createFolder($request->all());
            return ApiResponse::created($folder, 'Folder created');
        } catch (\Throwable $e) {
            Log::error('Folder create failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function move(Request $request)
    {
        try {
            $res = $this->service->move($request->all());
            return ApiResponse::success($res, 'Moved');
        } catch (\Throwable $e) {
            Log::error('Move failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function chunkInit(Request $request)
    {
        try {
            $res = $this->chunked->init($request);
            return ApiResponse::created($res, 'Chunked upload initialized');
        } catch (\Throwable $e) {
            Log::error('Chunk init failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function chunk(Request $request)
    {
        try {
            $res = $this->chunked->chunk($request);
            return ApiResponse::success($res, 'Chunk stored');
        } catch (\Throwable $e) {
            Log::error('Chunk upload failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function chunkComplete(Request $request)
    {
        try {
            $media = $this->chunked->complete($request);
            return ApiResponse::created($media, 'Asset uploaded');
        } catch (\Throwable $e) {
            Log::error('Chunk complete failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }
}

<?php

namespace App\Modules\Finder\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finder\Services\FinderService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

final class FinderController extends Controller
{
    public function __construct(
        private readonly FinderService $service
    ) {
    }

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $folderId = $request->query('folder_id');
        $folderId = $folderId !== null && $folderId !== '' ? (int) $folderId : null;
        if ($folderId !== null && $folderId <= 0) {
            $folderId = null;
        }

        $result = $this->service->index($folderId);
        return ApiResponse::success($result, 'Finder');
    }

    public function storeFolder(Request $request): \Illuminate\Http\JsonResponse
    {
        $folder = $this->service->createFolder($request->all());
        return ApiResponse::created($folder, 'Folder created');
    }

    public function updateFolder(int $id, Request $request): \Illuminate\Http\JsonResponse
    {
        $name = $request->input('name');
        if ($name === null || $name === '') {
            return ApiResponse::error('name is required', 422);
        }
        $folder = $this->service->renameFolder($id, (string) $name);
        return ApiResponse::success($folder, 'Folder updated');
    }

    public function destroyFolder(int $id): \Illuminate\Http\JsonResponse
    {
        $this->service->deleteFolder($id);
        return ApiResponse::success(null, 'Folder deleted');
    }

    public function move(Request $request): \Illuminate\Http\JsonResponse
    {
        $result = $this->service->move($request->all());
        return ApiResponse::success($result, 'Moved');
    }
}

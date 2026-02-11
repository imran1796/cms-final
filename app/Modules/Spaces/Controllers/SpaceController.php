<?php

namespace App\Modules\Spaces\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Spaces\Services\Interfaces\SpaceServiceInterface;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class SpaceController extends Controller
{
    public function __construct(private readonly SpaceServiceInterface $service)
    {
    }

    public function index()
    {
        return ApiResponse::success($this->service->list(), 'Spaces');
    }

    public function store(Request $request)
    {
        try {
            $space = $this->service->create($request->all());
            return ApiResponse::created($space, 'Space created');
        } catch (\Throwable $e) {
            Log::error('SpaceController@store failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->service->delete($id);
            return ApiResponse::success(null, 'Space deleted');
        } catch (\Throwable $e) {
            Log::error('SpaceController@destroy failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }
}

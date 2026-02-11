<?php

namespace App\Modules\System\System\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\System\Services\Interfaces\SystemServiceInterface;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Log;

final class SystemController extends Controller
{
    public function __construct(
        private readonly SystemServiceInterface $system
    ) {
    }

    public function health()
    {
        try {
            return ApiResponse::success($this->system->health(), 'Health');
        } catch (\Throwable $e) {
            Log::error('SystemController@health failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function info()
    {
        try {
            return ApiResponse::success($this->system->info(), 'Info');
        } catch (\Throwable $e) {
            Log::error('SystemController@info failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function stats()
    {
        try {
            return ApiResponse::success($this->system->stats(), 'Stats');
        } catch (\Throwable $e) {
            Log::error('SystemController@stats failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }
}

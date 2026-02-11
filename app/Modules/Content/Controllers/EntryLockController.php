<?php

declare(strict_types=1);

namespace App\Modules\Content\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Content\Services\Interfaces\EntryLockServiceInterface;
use App\Support\ApiResponse;
use App\Support\Exceptions\ApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class EntryLockController extends Controller
{
    private const MODULE = 'content.entry_lock';

    public function __construct(
        private readonly EntryLockServiceInterface $service,
    ) {}

    public function lock(string $collectionHandle, int $id, Request $request)
    {
        Log::info(self::MODULE . '.lock.start', [
            'request_id' => $request->header('X-Request-Id'),
            'user_id' => $request->user()?->id,
            'space_id' => \App\Support\CurrentSpace::id(),
            'collection' => $collectionHandle,
            'entry_id' => $id,
        ]);

        try {
            $data = $this->service->lock($collectionHandle, $id, $request);
            Log::info(self::MODULE . '.lock.success', [
                'request_id' => $request->header('X-Request-Id'),
                'user_id' => $request->user()?->id,
                'space_id' => \App\Support\CurrentSpace::id(),
                'collection' => $collectionHandle,
                'entry_id' => $id,
            ]);
            return ApiResponse::success($data, 'Entry locked');
        } catch (ApiException $e) {
            Log::warning(self::MODULE . '.lock.failed', [
                'request_id' => $request->header('X-Request-Id'),
                'user_id' => $request->user()?->id,
                'space_id' => \App\Support\CurrentSpace::id(),
                'collection' => $collectionHandle,
                'entry_id' => $id,
                'error_code' => $e->codeString(),
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function unlock(string $collectionHandle, int $id, Request $request)
    {
        Log::info(self::MODULE . '.unlock.start', [
            'request_id' => $request->header('X-Request-Id'),
            'user_id' => $request->user()?->id,
            'space_id' => \App\Support\CurrentSpace::id(),
            'collection' => $collectionHandle,
            'entry_id' => $id,
        ]);

        try {
            $data = $this->service->unlock($collectionHandle, $id);
            Log::info(self::MODULE . '.unlock.success', [
                'request_id' => $request->header('X-Request-Id'),
                'user_id' => $request->user()?->id,
                'space_id' => \App\Support\CurrentSpace::id(),
                'collection' => $collectionHandle,
                'entry_id' => $id,
            ]);
            return ApiResponse::success($data, 'Entry unlocked');
        } catch (ApiException $e) {
            Log::warning(self::MODULE . '.unlock.failed', [
                'request_id' => $request->header('X-Request-Id'),
                'user_id' => $request->user()?->id,
                'space_id' => \App\Support\CurrentSpace::id(),
                'collection' => $collectionHandle,
                'entry_id' => $id,
                'error_code' => $e->codeString(),
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

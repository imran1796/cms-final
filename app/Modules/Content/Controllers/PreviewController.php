<?php

declare(strict_types=1);

namespace App\Modules\Content\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Content\Services\Interfaces\PreviewServiceInterface;
use App\Support\ApiResponse;
use App\Support\Exceptions\ApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class PreviewController extends Controller
{
    private const MODULE = 'content.preview';

    public function __construct(
        private readonly PreviewServiceInterface $service,
    ) {}

    public function preview(Request $request, string $collectionHandle, int $id)
    {
        $spaceId = \App\Support\CurrentSpace::id() ?? 0;
        $token = (string) $request->query('token', '');

        Log::info(self::MODULE . '.preview.start', [
            'request_id' => $request->header('X-Request-Id'),
            'space_id' => $spaceId,
            'collection' => $collectionHandle,
            'entry_id' => $id,
        ]);

        try {
            $entry = $this->service->preview($spaceId, $collectionHandle, $id, $token);
            Log::info(self::MODULE . '.preview.success', [
                'request_id' => $request->header('X-Request-Id'),
                'space_id' => $spaceId,
                'collection' => $collectionHandle,
                'entry_id' => $id,
            ]);
            return ApiResponse::success($entry, 'Preview');
        } catch (ApiException $e) {
            Log::warning(self::MODULE . '.preview.failed', [
                'request_id' => $request->header('X-Request-Id'),
                'space_id' => $spaceId,
                'collection' => $collectionHandle,
                'entry_id' => $id,
                'error_code' => $e->codeString(),
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

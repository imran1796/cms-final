<?php

namespace App\Modules\Assets\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assets\Support\UploadLimitResolver;
use App\Modules\System\Authorization\Services\AuthorizationService;
use App\Support\ApiResponse;
use App\Support\Exceptions\ValidationApiException;
use Illuminate\Http\JsonResponse;

final class AssetConfigController extends Controller
{
    public function __construct(
        private readonly UploadLimitResolver $limits,
        private readonly AuthorizationService $authz,
    ) {
    }

    public function show(): JsonResponse
    {
        $this->authz->requirePermission('manage_assets');

        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null || $spaceId <= 0) {
            throw new ValidationApiException('X-Space-Id header is required', [
                'space_id' => ['Missing X-Space-Id'],
            ]);
        }

        $bytes = $this->limits->effectiveMaxBytes();

        return ApiResponse::success([
            'upload_max_filesize_bytes' => $this->limits->uploadMaxBytes(),
            'upload_max_filesize_human' => $this->limits->uploadMaxHuman(),
            'post_max_size_bytes' => $this->limits->postMaxBytes(),
            'post_max_size_human' => $this->limits->postMaxHuman(),
            'effective_max_upload_bytes' => $bytes,
            'effective_max_upload_human' => $this->limits->effectiveMaxHuman(),
            'strict_upload_validation' => (bool) config('cms_assets.strict_upload_validation', false),
        ], 'Assets config');
    }
}

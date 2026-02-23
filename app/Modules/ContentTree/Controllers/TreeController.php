<?php

namespace App\Modules\ContentTree\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ContentTree\Services\Interfaces\ContentTreeServiceInterface;
use App\Modules\ContentTree\Validators\TreeMoveValidator;
use App\Modules\ContentTree\Validators\TreeReorderValidator;
use App\Modules\System\Authorization\Services\AuthorizationService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

final class TreeController extends Controller
{
    public function __construct(
        private readonly ContentTreeServiceInterface $service,
        private readonly AuthorizationService $authz,
    ) {}

    public function tree(Request $request, string $collectionHandle)
    {
        $this->authz->requirePermission("{$collectionHandle}.read");

        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null || $spaceId <= 0) {
            abort(422, 'Missing space context (X-Space-Id)');
        }

        $limit = is_numeric((string) $request->query('limit')) ? (int) $request->query('limit') : 1000;
        $limit = max(1, min(5000, $limit));
        $skip = is_numeric((string) $request->query('skip')) ? (int) $request->query('skip') : 0;
        $skip = max(0, $skip);

        $data = $this->service->getTree($spaceId, $collectionHandle, [
            'limit' => $limit,
            'skip' => $skip,
        ]);

        return ApiResponse::success($data, 'Tree loaded', 200, [
            'limit' => $limit,
            'skip' => $skip,
        ]);
    }

    public function move(Request $request, string $collectionHandle, int $id)
    {
        $this->authz->requirePermission("{$collectionHandle}.update");

        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null || $spaceId <= 0) {
            abort(422, 'Missing space context (X-Space-Id)');
        }
        $actorId = optional($request->user())->id;

        $payload = TreeMoveValidator::validate($request->all());

        $this->service->moveEntry(
            spaceId: $spaceId,
            collectionHandle: $collectionHandle,
            entryId: $id,
            newParentEntryId: $payload['parent_id'] ?? null,
            position: $payload['position'] ?? null,
            actorId: $actorId
        );

        return ApiResponse::success('Moved');
    }

    public function reorder(Request $request, string $collectionHandle)
    {
        $this->authz->requirePermission("{$collectionHandle}.update");

        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null || $spaceId <= 0) {
            abort(422, 'Missing space context (X-Space-Id)');
        }
        $actorId = optional($request->user())->id;

        $payload = TreeReorderValidator::validate($request->all());

        $this->service->reorder(
            spaceId: $spaceId,
            collectionHandle: $collectionHandle,
            parentEntryId: $payload['parent_id'] ?? null,
            entryIds: $payload['order'],
            actorId: $actorId
        );

        return ApiResponse::success('Reordered');
    }
}

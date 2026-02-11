<?php

namespace App\Modules\Content\Revisions\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use App\Modules\Content\Revisions\Services\Interfaces\RevisionServiceInterface;

final class RevisionController extends Controller
{
    public function __construct(
        private readonly RevisionServiceInterface $service
    ) {}

    public function index(string $collectionHandle, int $id)
    {
        try {
            $items = $this->service->list($collectionHandle, $id);

            return ApiResponse::success([
                'items' => $items->map(fn ($r) => [
                    'id' => $r->id,
                    'entry_id' => $r->entry_id,
                    'diff' => $r->diff,
                    'created_by' => $r->created_by,
                    'created_at' => $r->created_at,
                ]),
            ], 'Revisions list');
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function restore(Request $request, string $collectionHandle, int $id)
    {
        try {
            $payload = $request->validate([
                'revision_id' => ['required', 'integer'],
            ]);

            $res = $this->service->restore($collectionHandle, $id, (int) $payload['revision_id']);

            return ApiResponse::success([
                'entry' => [
                    'id' => $res['entry']->id,
                    'data' => $res['entry']->data,
                ],
                'revision_id' => $res['revision_id'],
            ], 'Restored');
        } catch (\Throwable $e) {
            throw $e;
        }
    }
}

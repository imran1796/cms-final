<?php

namespace App\Modules\SavedViews\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SavedViews\Services\Interfaces\SavedViewServiceInterface;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

final class SavedViewController extends Controller
{
    public function __construct(
        private readonly SavedViewServiceInterface $service
    ) {
    }

    public function index(Request $request)
    {
        $resource = (string) $request->query('resource', '');
        $items = $this->service->list($resource);

        return ApiResponse::success($items, 'Saved views list');
    }

    public function store(Request $request)
    {
        $created = $this->service->create($request->all());

        return ApiResponse::success(
            message: 'Saved view created',
            data: $created,
            status: 201
        );
    }

    public function update(Request $request, int $id)
    {
        $updated = $this->service->update($id, $request->all());

        return ApiResponse::success($updated, 'Saved view updated');
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return ApiResponse::success(['id' => $id], 'Saved view deleted');
    }
}

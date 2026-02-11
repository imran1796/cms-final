<?php

namespace App\Modules\Search\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Search\Services\Interfaces\SearchServiceInterface;
use App\Modules\Search\Validators\SearchValidator;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class SearchController extends Controller
{
    public function __construct(private readonly SearchServiceInterface $service) {}

    public function search(Request $request, string $collectionHandle)
    {
        try {
            $query = SearchValidator::validate($request->query());
            $result = $this->service->search($collectionHandle, $query);

            return ApiResponse::success([
                'items' => $result['items'],
                'meta' => $result['meta'],
            ], 'Search results');
        } catch (\Throwable $e) {
            Log::error('Search failed', [
                'handle' => $collectionHandle,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

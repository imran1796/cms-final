<?php

namespace App\Modules\Forms\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use App\Support\Exceptions\ValidationApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Modules\Forms\Services\Interfaces\FormServiceInterface;

final class FormAdminController extends Controller
{
    public function __construct(private readonly FormServiceInterface $service) {}

    public function index()
    {
        try {
            return ApiResponse::success($this->service->list(), 'Forms list');
        } catch (\Throwable $e) {
            Log::error('Forms index failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function store(Request $request)
    {
        try {
            $form = $this->service->create($request->all());
            return ApiResponse::created($form, 'Form created');
        } catch (\Throwable $e) {
            Log::error('Forms store failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function show(string $id)
    {
        $formId = $this->toPositiveInt($id, 'id');
        try {
            return ApiResponse::success($this->service->get($formId), 'Form');
        } catch (\Throwable $e) {
            Log::error('Forms show failed', ['id' => $formId, 'message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(Request $request, string $id)
    {
        $formId = $this->toPositiveInt($id, 'id');
        try {
            $form = $this->service->update($formId, $request->all());
            return ApiResponse::success($form, 'Form updated');
        } catch (\Throwable $e) {
            Log::error('Forms update failed', ['id' => $formId, 'message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function destroy(string $id)
    {
        $formId = $this->toPositiveInt($id, 'id');
        try {
            $this->service->delete($formId);
            return ApiResponse::success([], 'Form deleted');
        } catch (\Throwable $e) {
            Log::error('Forms delete failed', ['id' => $formId, 'message' => $e->getMessage()]);
            throw $e;
        }
    }

    private function toPositiveInt(string $value, string $field): int
    {
        if ($value === '' || !ctype_digit($value)) {
            throw new ValidationApiException('Validation failed', [$field => ['Must be a positive integer']]);
        }

        $int = (int) $value;
        if ($int <= 0) {
            throw new ValidationApiException('Validation failed', [$field => ['Must be a positive integer']]);
        }

        return $int;
    }
}

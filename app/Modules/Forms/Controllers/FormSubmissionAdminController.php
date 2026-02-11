<?php

namespace App\Modules\Forms\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Forms\Services\Interfaces\FormSubmissionServiceInterface;
use App\Support\ApiResponse;
use App\Support\Exceptions\ValidationApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class FormSubmissionAdminController extends Controller
{
    public function __construct(private readonly FormSubmissionServiceInterface $service) {}

    public function index(string $id)
    {
        $formId = $this->toPositiveInt($id, 'id');
        try {
            return ApiResponse::success($this->service->listForForm($formId), 'Submissions list');
        } catch (\Throwable $e) {
            Log::error('Submissions index failed', ['form_id' => $formId, 'message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function show(string $id, string $submissionId)
    {
        $formId = $this->toPositiveInt($id, 'id');
        $subId = $this->toPositiveInt($submissionId, 'submissionId');
        try {
            return ApiResponse::success($this->service->get($formId, $subId), 'Submission');
        } catch (\Throwable $e) {
            Log::error('Submissions show failed', ['form_id' => $formId, 'sub_id' => $subId, 'message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(Request $request, string $id, string $submissionId)
    {
        $formId = $this->toPositiveInt($id, 'id');
        $subId = $this->toPositiveInt($submissionId, 'submissionId');
        try {
            $updated = $this->service->updateStatus($formId, $subId, $request->all());
            return ApiResponse::success($updated, 'Submission updated');
        } catch (\Throwable $e) {
            Log::error('Submissions update failed', ['form_id' => $formId, 'sub_id' => $subId, 'message' => $e->getMessage()]);
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

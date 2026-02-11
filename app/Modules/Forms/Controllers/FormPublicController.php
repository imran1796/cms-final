<?php

namespace App\Modules\Forms\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Forms\Services\Interfaces\FormSubmissionServiceInterface;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class FormPublicController extends Controller
{
    public function __construct(private readonly FormSubmissionServiceInterface $service) {}

    public function submit(Request $request, string $handle)
    {
        try {
            $res = $this->service->submit($handle, $request->all());
            return ApiResponse::created($res, 'Submission received');
        } catch (\Throwable $e) {
            Log::error('Public form submit failed', ['handle' => $handle, 'message' => $e->getMessage()]);
            throw $e;
        }
    }
}

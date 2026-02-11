<?php

namespace App\Modules\Forms\Services\Interfaces;

interface FormSubmissionServiceInterface
{
    public function submit(string $handle, array $payload): array;

    public function listForForm(int $formId): array;
    public function get(int $formId, int $submissionId): array;
    public function updateStatus(int $formId, int $submissionId, array $input): array;
}

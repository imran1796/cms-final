<?php

namespace App\Modules\Forms\Repositories\Interfaces;

use App\Modules\Forms\Models\FormSubmission;

interface FormSubmissionRepositoryInterface
{
    public function listForForm(int $spaceId, int $formId): array;
    public function find(int $spaceId, int $formId, int $submissionId): ?FormSubmission;
    public function create(array $data): FormSubmission;
    public function update(FormSubmission $submission, array $data): FormSubmission;
}

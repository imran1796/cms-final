<?php

namespace App\Modules\Forms\Repositories;

use App\Modules\Forms\Models\FormSubmission;
use App\Modules\Forms\Repositories\Interfaces\FormSubmissionRepositoryInterface;

final class FormSubmissionRepository implements FormSubmissionRepositoryInterface
{
    public function listForForm(int $spaceId, int $formId): array
    {
        return FormSubmission::query()
            ->where('space_id', $spaceId)
            ->where('form_id', $formId)
            ->orderByDesc('id')
            ->get()
            ->all();
    }

    public function listForFormPaginated(int $spaceId, int $formId, int $limit, int $skip): array
    {
        $query = FormSubmission::query()
            ->where('space_id', $spaceId)
            ->where('form_id', $formId)
            ->orderByDesc('id');

        $total = (clone $query)->count();
        $items = $query->skip($skip)->take($limit)->get()->all();

        return ['items' => $items, 'total' => $total];
    }

    public function find(int $spaceId, int $formId, int $submissionId): ?FormSubmission
    {
        return FormSubmission::query()
            ->where('space_id', $spaceId)
            ->where('form_id', $formId)
            ->where('id', $submissionId)
            ->first();
    }

    public function create(array $data): FormSubmission
    {
        return FormSubmission::create($data);
    }

    public function update(FormSubmission $submission, array $data): FormSubmission
    {
        $submission->fill($data);
        $submission->save();
        return $submission;
    }
}

<?php

namespace App\Modules\Forms\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Support\Exceptions\NotFoundApiException;
use App\Support\Exceptions\ForbiddenApiException;
use App\Modules\Forms\Services\Interfaces\FormSubmissionServiceInterface;
use App\Modules\Forms\Repositories\Interfaces\FormRepositoryInterface;
use App\Modules\Forms\Repositories\Interfaces\FormSubmissionRepositoryInterface;
use App\Modules\Forms\Validators\SubmissionValidator;
use App\Modules\System\Authorization\Services\AuthorizationService;
use App\Modules\System\Audit\Services\Interfaces\AuditLogServiceInterface;

final class FormSubmissionService implements FormSubmissionServiceInterface
{
    public function __construct(
        private readonly FormRepositoryInterface $forms,
        private readonly FormSubmissionRepositoryInterface $subs,
        private readonly AuthorizationService $authz,
        private readonly AuditLogServiceInterface $audit,
    ) {
    }

    public function submit(string $handle, array $payload): array
    {
        $spaceId = $this->requireSpaceId();

        $form = $this->forms->findByHandle($spaceId, $handle);
        if (!$form) throw new NotFoundApiException('Resource not found');

        $hp = data_get($payload, 'data._hp');
        if (!empty($hp)) {
            $status = 'spam';
        } else {
            $status = 'new';
        }

        $validatedData = SubmissionValidator::validate((array)$form->fields, $payload);

        $meta = [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'headers' => [
                'accept_language' => request()->header('Accept-Language'),
                'referer' => request()->header('Referer'),
            ],
        ];

        return DB::transaction(function () use ($spaceId, $form, $validatedData, $meta, $status) {
            $submission = $this->subs->create([
                'space_id' => $spaceId,
                'form_id' => (int)$form->id,
                'status' => $status,
                'data' => $validatedData,
                'meta' => $meta,
                'created_by' => auth()->id(), // usually null for public
            ]);

            Log::info('Form submitted', [
                'space_id' => $spaceId,
                'form_id' => $form->id,
                'submission_id' => $submission->id,
                'status' => $status,
            ]);

            return [
                'id' => $submission->id,
                'status' => $submission->status,
            ];
        });
    }

    public function listForForm(int $formId, array $params = []): array
    {
        $spaceId = $this->requireSpaceId();
        $this->authz->requirePermission('manage_forms');

        $form = $this->forms->find($spaceId, $formId);
        if (!$form) throw new NotFoundApiException('Resource not found');

        $limit = isset($params['limit']) && is_numeric($params['limit']) ? (int) $params['limit'] : 25;
        $limit = max(1, min(100, $limit));
        $skip = isset($params['skip']) && is_numeric($params['skip']) ? (int) $params['skip'] : 0;
        $skip = max(0, $skip);

        $result = $this->subs->listForFormPaginated($spaceId, $formId, $limit, $skip);
        $items = array_map(fn($s) => $s->toArray(), $result['items']);

        return [
            'items' => $items,
            'total' => (int) $result['total'],
            'limit' => $limit,
            'skip' => $skip,
        ];
    }

    public function get(int $formId, int $submissionId): array
    {
        $spaceId = $this->requireSpaceId();
        $this->authz->requirePermission('manage_forms');

        $form = $this->forms->find($spaceId, $formId);
        if (!$form) throw new NotFoundApiException('Resource not found');

        $sub = $this->subs->find($spaceId, $formId, $submissionId);
        if (!$sub) throw new NotFoundApiException('Resource not found');

        return $sub->toArray();
    }

    public function updateStatus(int $formId, int $submissionId, array $input): array
    {
        $spaceId = $this->requireSpaceId();
        $this->authz->requirePermission('manage_forms');

        $form = $this->forms->find($spaceId, $formId);
        if (!$form) throw new NotFoundApiException('Resource not found');

        $sub = $this->subs->find($spaceId, $formId, $submissionId);
        if (!$sub) throw new NotFoundApiException('Resource not found');

        $status = (string)($input['status'] ?? '');
        $allowed = ['new','processed','spam'];
        if (!in_array($status, $allowed, true)) {
            throw new \App\Support\Exceptions\ValidationApiException('Validation failed', [
                'status' => ['status must be one of: new, processed, spam'],
            ]);
        }

        $before = $sub->toArray();

        $updated = DB::transaction(function () use ($sub, $status) {
            return $this->subs->update($sub, ['status' => $status]);
        });

        $this->audit->write(
            action: 'submission.status_update',
            resource: 'forms',
            diff: ['before' => $before, 'after' => $updated->toArray()],
            spaceId: $spaceId,
            actorId: auth()->id(),
        );

        Log::info('Submission status updated', [
            'space_id' => $spaceId,
            'form_id' => $formId,
            'submission_id' => $submissionId,
            'status' => $status,
            'user_id' => auth()->id(),
        ]);

        return $updated->toArray();
    }

    private function requireSpaceId(): int
    {
        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null || $spaceId <= 0) {
            throw new \App\Support\Exceptions\ValidationApiException('X-Space-Id header is required', [
                'space_id' => ['Missing X-Space-Id'],
            ]);
        }
        return $spaceId;
    }
}

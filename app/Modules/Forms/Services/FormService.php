<?php

namespace App\Modules\Forms\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Support\Exceptions\NotFoundApiException;
use App\Modules\Forms\Services\Interfaces\FormServiceInterface;
use App\Modules\Forms\Repositories\Interfaces\FormRepositoryInterface;
use App\Modules\Forms\Validators\FormSchemaValidator;
use App\Modules\System\Authorization\Services\AuthorizationService;
use App\Modules\System\Audit\Services\Interfaces\AuditLogServiceInterface;

final class FormService implements FormServiceInterface
{
    public function __construct(
        private readonly FormRepositoryInterface $forms,
        private readonly AuthorizationService $authz,
        private readonly AuditLogServiceInterface $audit,
    ) {
    }

    public function list(): array
    {
        $spaceId = $this->requireSpaceId();
        $this->authz->requirePermission('manage_forms');

        $rows = $this->forms->list($spaceId);
        return array_map(fn($f) => $f->toArray(), $rows);
    }

    public function get(int $id): array
    {
        $spaceId = $this->requireSpaceId();
        $this->authz->requirePermission('manage_forms');

        $form = $this->forms->find($spaceId, $id);
        if (!$form) throw new NotFoundApiException('Form not found');

        return $form->toArray();
    }

    public function create(array $input): array
    {
        $spaceId = $this->requireSpaceId();
        $this->authz->requirePermission('manage_forms');

        $validated = FormSchemaValidator::validate($input);

        return DB::transaction(function () use ($spaceId, $validated) {
            $form = $this->forms->create([
                'space_id' => $spaceId,
                'handle' => $validated['handle'],
                'title' => $validated['title'],
                'fields' => $validated['fields'],
                'settings' => $validated['settings'],
            ]);

            $this->audit->write(
                action: 'form.create',
                resource: 'forms',
                diff: ['after' => $form->toArray()],
                spaceId: $spaceId,
                actorId: auth()->id(),
            );

            Log::info('Form created', ['space_id' => $spaceId, 'form_id' => $form->id, 'user_id' => auth()->id()]);

            return $form->toArray();
        });
    }

    public function update(int $id, array $input): array
    {
        $spaceId = $this->requireSpaceId();
        $this->authz->requirePermission('manage_forms');

        $form = $this->forms->find($spaceId, $id);
        if (!$form) throw new NotFoundApiException('Form not found');

        $before = $form->toArray();
        $validated = FormSchemaValidator::validate($input);

        return DB::transaction(function () use ($form, $spaceId, $validated, $before) {
            $updated = $this->forms->update($form, [
                'handle' => $validated['handle'],
                'title' => $validated['title'],
                'fields' => $validated['fields'],
                'settings' => $validated['settings'],
            ]);

            $this->audit->write(
                action: 'form.update',
                resource: 'forms',
                diff: ['before' => $before, 'after' => $updated->toArray()],
                spaceId: $spaceId,
                actorId: auth()->id(),
            );

            Log::info('Form updated', ['space_id' => $spaceId, 'form_id' => $updated->id, 'user_id' => auth()->id()]);

            return $updated->toArray();
        });
    }

    public function delete(int $id): void
    {
        $spaceId = $this->requireSpaceId();
        $this->authz->requirePermission('manage_forms');

        $form = $this->forms->find($spaceId, $id);
        if (!$form) throw new NotFoundApiException('Form not found');

        $before = $form->toArray();

        DB::transaction(function () use ($form, $spaceId, $before) {
            $this->forms->delete($form);

            $this->audit->write(
                action: 'form.delete',
                resource: 'forms',
                diff: ['before' => $before],
                spaceId: $spaceId,
                actorId: auth()->id(),
            );

            Log::info('Form deleted', ['space_id' => $spaceId, 'form_id' => $before['id'] ?? null, 'user_id' => auth()->id()]);
        });
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

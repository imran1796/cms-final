<?php

namespace App\Modules\Content\Revisions\Services;

use App\Modules\Content\Revisions\Repositories\Interfaces\RevisionRepositoryInterface;
use App\Modules\Content\Revisions\Services\Interfaces\RevisionServiceInterface;
use App\Modules\System\Authorization\Services\AuthorizationService;
use App\Modules\System\Audit\Services\Interfaces\AuditLogServiceInterface;
use App\Support\Exceptions\NotFoundApiException;
use App\Support\Exceptions\ValidationApiException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class RevisionService implements RevisionServiceInterface
{
    public function __construct(
        private readonly RevisionRepositoryInterface $revisions,
        private readonly AuthorizationService $authz,
        private readonly AuditLogServiceInterface $audit,
    ) {}

    public function list(string $collectionHandle, int $entryId): Collection
    {
        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null || $spaceId <= 0) {
            throw new ValidationApiException('Validation failed', ['space_id' => ['Missing space context']]);
        }
        $this->authz->requirePermission($collectionHandle . '.read');

        return $this->revisions->listForEntry($spaceId, $entryId);
    }

    public function restore(string $collectionHandle, int $entryId, int $revisionId): array
    {
        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null || $spaceId <= 0) {
            throw new ValidationApiException('Validation failed', ['space_id' => ['Missing space context']]);
        }
        $this->authz->requirePermission($collectionHandle . '.update');

        $rev = $this->revisions->findInSpace($spaceId, $revisionId);
        if (!$rev || (int) $rev->entry_id !== $entryId) {
            throw new NotFoundApiException('Revision not found');
        }

        $snapshot = $rev->snapshot;

        return DB::transaction(function () use ($spaceId, $entryId, $snapshot, $collectionHandle, $revisionId) {
            $entry = \App\Models\Entry::query()
                ->where('space_id', $spaceId)
                ->where('id', $entryId)
                ->first();

            if (!$entry) {
                throw new NotFoundApiException('Entry not found');
            }

            $before = (array) $entry->data;

            $entry->data = $snapshot['data'] ?? $snapshot;
            $entry->save();

            $this->audit->write(
                action: 'entry.restore',
                resource: $collectionHandle . ':' . $entryId,
                diff: [
                    'revision_id' => $revisionId,
                    'before' => $before,
                    'after' => (array) $entry->data,
                ],
                spaceId: $spaceId,
                actorId: optional(auth()->user())->id
            );

            return [
                'entry' => $entry,
                'revision_id' => $revisionId,
            ];
        });
    }

    public function createOnUpdate(string $collectionHandle, int $entryId, array $beforeSnapshot, array $afterData): void
    {
        $spaceId = \App\Support\CurrentSpace::id();
        if ($spaceId === null || $spaceId <= 0) {
            throw new ValidationApiException('Validation failed', ['space_id' => ['Missing space context']]);
        }

        $diff = $this->simpleDiff($beforeSnapshot['data'] ?? $beforeSnapshot, $afterData);

        $this->revisions->create([
            'space_id' => $spaceId,
            'entry_id' => $entryId,
            'snapshot' => $beforeSnapshot,
            'diff' => $diff,
            'created_by' => optional(auth()->user())->id,
        ]);
    }

    private function simpleDiff(array $before, array $after): array
    {
        $changed = [];

        foreach ($after as $k => $v) {
            $beforeVal = $before[$k] ?? null;
            if ($beforeVal !== $v) {
                $changed[$k] = [
                    'from' => $beforeVal,
                    'to' => $v,
                ];
            }
        }

        return $changed;
    }
}

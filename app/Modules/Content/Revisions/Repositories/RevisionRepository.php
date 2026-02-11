<?php

namespace App\Modules\Content\Revisions\Repositories;

use App\Modules\Content\Revisions\Models\Revision;
use App\Modules\Content\Revisions\Repositories\Interfaces\RevisionRepositoryInterface;
use Illuminate\Support\Collection;

final class RevisionRepository implements RevisionRepositoryInterface
{
    public function listForEntry(int $spaceId, int $entryId): Collection
    {
        return Revision::query()
            ->where('space_id', $spaceId)
            ->where('entry_id', $entryId)
            ->orderByDesc('id')
            ->get();
    }

    public function create(array $data): Revision
    {
        return Revision::create($data);
    }

    public function findInSpace(int $spaceId, int $revisionId): ?Revision
    {
        return Revision::query()
            ->where('space_id', $spaceId)
            ->where('id', $revisionId)
            ->first();
    }
}

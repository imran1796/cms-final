<?php

namespace App\Modules\Content\Revisions\Repositories\Interfaces;

use Illuminate\Support\Collection;
use App\Modules\Content\Revisions\Models\Revision;

interface RevisionRepositoryInterface
{
    public function listForEntry(int $spaceId, int $entryId): Collection;

    public function create(array $data): Revision;

    public function findInSpace(int $spaceId, int $revisionId): ?Revision;
}

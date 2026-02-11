<?php

namespace App\Modules\SavedViews\Repositories\Interfaces;

use Illuminate\Support\Collection;
use App\Modules\SavedViews\Models\SavedView;

interface SavedViewRepositoryInterface
{
    public function list(int $userId, ?int $spaceId, ?string $resource): Collection;

    public function findOwnedOrFail(int $id, int $userId, ?int $spaceId): SavedView;

    public function create(array $data): SavedView;

    public function update(SavedView $view, array $data): SavedView;

    public function delete(SavedView $view): void;
}

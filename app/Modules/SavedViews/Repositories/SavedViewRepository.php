<?php

namespace App\Modules\SavedViews\Repositories;

use Illuminate\Support\Collection;
use App\Modules\SavedViews\Models\SavedView;
use App\Modules\SavedViews\Repositories\Interfaces\SavedViewRepositoryInterface;

final class SavedViewRepository implements SavedViewRepositoryInterface
{
    public function list(int $userId, ?int $spaceId, ?string $resource): Collection
    {
        $q = SavedView::query()->where('user_id', $userId);

        if ($spaceId !== null) {
            $q->where('space_id', $spaceId);
        } else {
            $q->whereNull('space_id');
        }

        if ($resource) {
            $q->where('resource', $resource);
        }

        return $q->orderBy('resource')->orderBy('name')->get();
    }

    public function findOwnedOrFail(int $id, int $userId, ?int $spaceId): SavedView
    {
        $q = SavedView::query()
            ->where('id', $id)
            ->where('user_id', $userId);

        if ($spaceId !== null) {
            $q->where('space_id', $spaceId);
        } else {
            $q->whereNull('space_id');
        }

        return $q->firstOrFail();
    }

    public function create(array $data): SavedView
    {
        return SavedView::create($data);
    }

    public function update(SavedView $view, array $data): SavedView
    {
        $view->fill($data);
        $view->save();

        return $view->refresh();
    }

    public function delete(SavedView $view): void
    {
        $view->delete();
    }
}

<?php

namespace App\Modules\Spaces\Repositories;

use App\Models\Space;
use App\Modules\Spaces\Repositories\Interfaces\SpaceRepositoryInterface;
use Illuminate\Support\Collection;

final class SpaceRepository implements SpaceRepositoryInterface
{
    public function list(): Collection
    {
        return Space::query()
            ->orderBy('id', 'desc')
            ->get();
    }

    public function create(array $data): Space
    {
        return Space::create($data);
    }

    public function findOrFail(int $id): Space
    {
        return Space::query()->findOrFail($id);
    }

    public function delete(Space $space): void
    {
        $space->delete();
    }
}

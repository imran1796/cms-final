<?php

declare(strict_types=1);

namespace App\Modules\Spaces\Repositories\Interfaces;

use App\Models\Space;
use Illuminate\Support\Collection;

interface SpaceRepositoryInterface
{
    public function list(): Collection;

    public function create(array $data): Space;

    public function findOrFail(int $id): Space;

    public function delete(Space $space): void;
}

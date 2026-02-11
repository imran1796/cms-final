<?php

namespace App\Modules\Spaces\Services\Interfaces;

use App\Models\Space;
use Illuminate\Support\Collection;

interface SpaceServiceInterface
{
    public function list(): Collection;

    public function create(array $input): Space;

    public function delete(int $id): void;
}

<?php

namespace App\Modules\SavedViews\Services\Interfaces;

use Illuminate\Support\Collection;
use App\Modules\SavedViews\Models\SavedView;

interface SavedViewServiceInterface
{
    public function list(?string $resource): Collection;

    public function create(array $input): SavedView;

    public function update(int $id, array $input): SavedView;

    public function delete(int $id): void;
}

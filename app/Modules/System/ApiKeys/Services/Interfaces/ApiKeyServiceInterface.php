<?php

namespace App\Modules\System\ApiKeys\Services\Interfaces;

use App\Models\ApiKey;
use Illuminate\Support\Collection;

interface ApiKeyServiceInterface
{
    public function list(): Collection;

    public function create(array $input): array;

    public function update(int $id, array $input): ApiKey;

    public function delete(int $id): void;

    public function regenerate(int $id): array;
}

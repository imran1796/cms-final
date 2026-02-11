<?php

namespace App\Modules\Content\Services;

use App\Models\Collection;
use Illuminate\Support\Collection as SupportCollection;

interface ContentTypeServiceInterface
{
    public function list(): SupportCollection;

    public function create(array $input): Collection;

    public function get(int $id): Collection;

    public function update(int $id, array $input): Collection;

    public function delete(int $id): void;

    public function addField(int $id, array $fieldInput): Collection;

    public function updateField(int $id, string $fieldId, array $fieldInput): Collection;

    public function deleteField(int $id, string $fieldId): Collection;
}

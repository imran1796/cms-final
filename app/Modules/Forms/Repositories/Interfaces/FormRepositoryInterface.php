<?php

namespace App\Modules\Forms\Repositories\Interfaces;

use App\Modules\Forms\Models\Form;

interface FormRepositoryInterface
{
    public function list(int $spaceId): array;
    public function listPaginated(int $spaceId, int $limit, int $skip): array;
    public function find(int $spaceId, int $id): ?Form;
    public function findByHandle(int $spaceId, string $handle): ?Form;
    public function create(array $data): Form;
    public function update(Form $form, array $data): Form;
    public function delete(Form $form): void;
}

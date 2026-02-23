<?php

namespace App\Modules\Forms\Services\Interfaces;

interface FormServiceInterface
{
    public function list(array $params = []): array;
    public function get(int $id): array;
    public function create(array $input): array;
    public function update(int $id, array $input): array;
    public function delete(int $id): void;
}

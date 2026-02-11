<?php

namespace App\Modules\ContentDelivery\Services\Interfaces;

interface PublicContentServiceInterface
{
    public function list(string $collectionHandle, array $query): array;

    public function get(string $collectionHandle, int $id, array $query): array;
}

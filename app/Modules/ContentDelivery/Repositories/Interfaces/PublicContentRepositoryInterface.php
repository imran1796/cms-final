<?php

namespace App\Modules\ContentDelivery\Repositories\Interfaces;

interface PublicContentRepositoryInterface
{
    public function listPublished(int $spaceId, int $collectionId, array $parsed): array;

    public function getPublished(int $spaceId, int $collectionId, int $id): ?object;
}

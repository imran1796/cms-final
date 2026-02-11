<?php

namespace App\Modules\Search\Services\Interfaces;

use App\Modules\Search\DTO\SearchQuery;

interface SearchServiceInterface
{
    public function search(string $collectionHandle, SearchQuery $query): array;
}

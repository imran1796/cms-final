<?php

namespace App\Modules\Search\DTO;

final class SearchQuery
{
    public function __construct(
        public readonly string $q,
        public readonly int $limit,
        public readonly int $offset,
        public readonly array $filters,
        public readonly ?string $sort,
    ) {}
}

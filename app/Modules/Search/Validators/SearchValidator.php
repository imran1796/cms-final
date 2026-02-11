<?php

namespace App\Modules\Search\Validators;

use App\Modules\Search\DTO\SearchQuery;
use App\Support\Exceptions\ValidationApiException;

final class SearchValidator
{
    public static function validate(array $query): SearchQuery
    {
        $q = (string)($query['q'] ?? '');
        $q = trim($q);

        if ($q === '') {
            throw new ValidationApiException('Validation failed', [
                'q' => ['q is required'],
            ]);
        }

        $maxLen = (int)config('cms_search.max_query_length', 120);
        if (mb_strlen($q) > $maxLen) {
            throw new ValidationApiException('Validation failed', [
                'q' => ["q too long (max {$maxLen})"],
            ]);
        }

        $limit = isset($query['limit']) && is_numeric($query['limit']) ? (int)$query['limit'] : (int)config('cms_search.default_limit', 10);
        $limit = max(1, min($limit, (int)config('cms_search.max_limit', 50)));

        $offset = isset($query['offset']) && is_numeric($query['offset']) ? (int)$query['offset'] : 0;
        $offset = max(0, $offset);

        $filters = is_array($query['filter'] ?? null) ? (array)$query['filter'] : [];

        $sort = isset($query['sort']) ? (string)$query['sort'] : null;

        return new SearchQuery(
            q: $q,
            limit: $limit,
            offset: $offset,
            filters: $filters,
            sort: $sort
        );
    }
}

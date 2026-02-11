<?php

namespace App\Modules\ContentDelivery\Support;

final class PublicQueryParser
{
    public static function parse(array $query): array
    {
        $maxLimit = (int) (config('cms.public.max_limit') ?? 50);
        $maxDepth = (int) (config('cms.public.max_depth') ?? 2);

        $defaultLimit = (int) (config('cms.public.default_limit') ?? 10);
        $limit = isset($query['limit']) ? (int) $query['limit'] : $defaultLimit;
        if ($limit < 1) $limit = 1;
        if ($limit > $maxLimit) $limit = $maxLimit;

        $sort = isset($query['sort']) ? (string) $query['sort'] : null;

        $locale = isset($query['locale']) ? (string) $query['locale'] : null;

        $populate = [];
        if (!empty($query['populate'])) {
            $populate = array_values(array_filter(array_map('trim', explode(',', (string)$query['populate']))));
        }

        $fields = [];
        if (!empty($query['fields'])) {
            $fields = array_values(array_filter(array_map('trim', explode(',', (string)$query['fields']))));
        }

        $filters = [];
        if (isset($query['filter']) && is_array($query['filter'])) {
            foreach ($query['filter'] as $k => $v) {
                if (is_string($k) && (is_string($v) || is_numeric($v))) {
                    $filters[$k] = (string) $v;
                }
            }
        }

        return [
            'limit' => $limit,
            'sort' => $sort,
            'locale' => $locale,
            'populate' => $populate,
            'fields' => $fields,
            'filters' => $filters,
            'max_depth' => $maxDepth,
        ];
    }
}

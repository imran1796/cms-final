<?php

return [
    // Hard query limits (public endpoint)
    'max_limit' => env('CMS_SEARCH_MAX_LIMIT', 50),
    'default_limit' => env('CMS_SEARCH_DEFAULT_LIMIT', 10),
    'max_query_length' => env('CMS_SEARCH_MAX_QUERY_LENGTH', 120),

    // 'scout' | 'db' | 'auto'. db = always use DB (title/slug LIKE). auto = Scout if available, else DB.
    // Default 'db' so search works without Meilisearch. Set CMS_SEARCH_ENGINE=auto for Scout when Meilisearch is configured.
    'engine' => env('CMS_SEARCH_ENGINE', 'db'),

    // When Scout driver is meilisearch, search uses Meilisearch. If unavailable or driver is
    // collection/null, fallback to DB. Set SCOUT_DRIVER=meilisearch and run scout:sync-index-settings.
    'fallback_db' => env('CMS_SEARCH_FALLBACK_DB', true),

    // Default fields searched in DB fallback
    'fallback_fields' => ['title', 'slug'],

    // Per-collection search config defaults (when collection.settings.search omits them)
    'default_searchable_attributes' => ['title', 'slug'],
    'default_filterable_attributes' => ['status'],
    'default_sortable_attributes' => ['created_at', 'published_at', 'id'],

    // Global "public throttling"
    'throttle' => env('CMS_PUBLIC_THROTTLE', '60,1'), // 60 requests per 1 minute
];

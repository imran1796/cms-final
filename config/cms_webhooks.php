<?php

return [
    // Global webhook URLs (comma-separated or array)
    'global_urls' => array_filter(explode(',', env('CMS_WEBHOOKS_GLOBAL_URLS', ''))),

    // Per-space webhooks: CMS_WEBHOOKS_SPACE_1=http://example.com/webhook
    'spaces' => [
        // Example: 1 => env('CMS_WEBHOOKS_SPACE_1'),
    ],

    // Per-collection webhooks: CMS_WEBHOOKS_COLLECTION_1_POSTS=http://example.com/posts
    'collections' => [
        // Example: 1 => ['posts' => env('CMS_WEBHOOKS_COLLECTION_1_POSTS')],
    ],

    // HMAC secret for signing webhook payloads (optional)
    'secret' => env('CMS_WEBHOOKS_SECRET'),

    // Timeout in seconds for webhook requests
    'timeout_seconds' => (int) env('CMS_WEBHOOKS_TIMEOUT', 10),

    // Queue webhooks for retry on failure (requires job implementation)
    'retry_on_failure' => env('CMS_WEBHOOKS_RETRY_ON_FAILURE', false),

    // Retry policy
    'retry_queue' => env('CMS_WEBHOOKS_RETRY_QUEUE', 'default'),
    'retry_tries' => (int) env('CMS_WEBHOOKS_RETRY_TRIES', 3),
    'retry_backoff_seconds' => array_values(array_filter(array_map('trim', explode(',', (string) env('CMS_WEBHOOKS_RETRY_BACKOFF_SECONDS', '5,15,60'))))),

    // Idempotency marker retention for successful webhook dispatches
    'idempotency_ttl_seconds' => (int) env('CMS_WEBHOOKS_IDEMPOTENCY_TTL_SECONDS', 86400),
];

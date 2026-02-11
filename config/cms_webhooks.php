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
];

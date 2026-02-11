<?php

return [
    'public' => [
        'max_limit' => (int) (env('CMS_PUBLIC_MAX_LIMIT', 50)),
        'max_depth' => (int) (env('CMS_PUBLIC_MAX_DEPTH', 2)),
        'default_limit' => (int) (env('CMS_PUBLIC_DEFAULT_LIMIT', 10)),
    ],

    'entry_lock_ttl_seconds' => (int) (env('CMS_ENTRY_LOCK_TTL_SECONDS', 300)),
];

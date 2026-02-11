<?php

return [
    'version' => env('SYSTEM_VERSION', '0.1.0'),
    'build' => env('SYSTEM_BUILD', 'dev'),
    'flags' => [
        'redis_enabled' => (bool) env('REDIS_ENABLED', false),
    ],
];

<?php

$corsOrigins = array_values(array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'https://example.com')))));
$supportsCredentials = (bool) env('CORS_SUPPORTS_CREDENTIALS', true);
if ($supportsCredentials) {
    $corsOrigins = array_values(array_filter($corsOrigins, static fn(string $origin): bool => $origin !== '*'));
}

return [

    'paths' => ['api/*', 'storage/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => $corsOrigins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'Origin',
        'X-Space-Id',
        'X-Space-Handle',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => $supportsCredentials,

];

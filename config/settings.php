<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Allowed setting keys (optional whitelist)
    |--------------------------------------------------------------------------
    | If set, only these keys can be updated via the Settings API. Leave null
    | to allow any key (key must be string, max 191 chars).
    |
    */

    'allowed_keys' => env('SETTINGS_ALLOWED_KEYS') ? array_filter(array_map('trim', explode(',', env('SETTINGS_ALLOWED_KEYS')))) : null,
];

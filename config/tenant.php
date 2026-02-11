<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Subdomain tenant resolution
    |--------------------------------------------------------------------------
    | When enabled, the first segment of the request host (e.g. "main" from
    | main.example.com) is used to resolve a Space by handle. Reserved
    | subdomains are never treated as space handles.
    |
    */

    'subdomain_enabled' => (bool) env('TENANT_SUBDOMAIN_ENABLED', false),

    'subdomain_reserved' => array_map('trim', array_filter(explode(',', env('TENANT_SUBDOMAIN_RESERVED', 'www,api,app')))),

];

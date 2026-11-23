<?php

return [

    /*
    | The web client is served from the same origin (nginx proxies /api), so no
    | cross-origin access is needed by default. Laravel's default is to allow every
    | origin; we only allow origins listed in CORS_ALLOWED_ORIGINS (comma-separated),
    | e.g. a separately hosted storefront or the Vite dev server.
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')),
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Content-Type'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => false,

];

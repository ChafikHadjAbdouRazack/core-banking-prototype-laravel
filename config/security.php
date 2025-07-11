<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy Settings
    |--------------------------------------------------------------------------
    |
    | Configure the Content Security Policy (CSP) headers for your application.
    | These settings help prevent XSS attacks and other code injection attacks.
    |
    */

    'csp' => [
        // Font sources
        'font_sources' => env('CSP_FONT_SOURCES', 'https://fonts.gstatic.com,https://fonts.bunny.net'),

        // Style sources
        'style_sources' => env('CSP_STYLE_SOURCES', 'https://fonts.googleapis.com,https://fonts.bunny.net'),

        // Script sources
        'script_sources' => env('CSP_SCRIPT_SOURCES', 'https://cdn.jsdelivr.net'),

        // API endpoints
        'api_endpoint' => env('CSP_API_ENDPOINT', 'https://api.finaegis.org'),
        'ws_endpoint'  => env('CSP_WS_ENDPOINT', 'wss://ws.finaegis.org'),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTPS Settings
    |--------------------------------------------------------------------------
    |
    | Configure when HTTPS should be enforced.
    |
    */

    'force_https' => env('FORCE_HTTPS', env('APP_ENV') === 'production'),

];

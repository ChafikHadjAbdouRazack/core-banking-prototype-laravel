<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. You may set this to
    | any of the connections defined in the "connections" array below.
    |
    | Supported: "pusher", "ably", "redis", "log", "null"
    |
    */

    'default' => env('BROADCAST_CONNECTION', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the broadcast connections that will be used
    | to broadcast events to other systems or over websockets. Samples of
    | each available type of connection are provided inside this array.
    |
    */

    'connections' => [

        'pusher' => [
            'driver'  => 'pusher',
            'key'     => env('PUSHER_APP_KEY'),
            'secret'  => env('PUSHER_APP_SECRET'),
            'app_id'  => env('PUSHER_APP_ID'),
            'options' => array_filter([
                'cluster' => env('PUSHER_APP_CLUSTER', 'eu'),
                'useTLS'  => env('PUSHER_SCHEME', 'https') === 'https',
                // Only set host/port for self-hosted Soketi.
                // For Pusher cloud, leave PUSHER_HOST unset so the SDK resolves via cluster.
                'host'         => env('PUSHER_HOST') ?: null,
                'port'         => env('PUSHER_HOST') ? (int) env('PUSHER_PORT', 6001) : null,
                'scheme'       => env('PUSHER_SCHEME', 'https'),
                'encrypted'    => env('PUSHER_SCHEME', 'https') === 'https',
                'curl_options' => [
                    CURLOPT_SSL_VERIFYHOST => env('PUSHER_SCHEME', 'https') === 'https' ? 2 : 0,
                    CURLOPT_SSL_VERIFYPEER => env('PUSHER_SCHEME', 'https') === 'https',
                ],
            ], fn ($v) => $v !== null),
            'client_options' => [
                // Guzzle client options for webhook callbacks
            ],
        ],

        'ably' => [
            'driver' => 'ably',
            'key'    => env('ABLY_KEY'),
        ],

        'redis' => [
            'driver'     => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];

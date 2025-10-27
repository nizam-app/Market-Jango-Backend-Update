<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster that will be used by the
    | framework when an event needs to be broadcast. We use Reverb here.
    |
    */

    'default' => env('BROADCAST_CONNECTION', 'reverb'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | Define all the broadcast connections that will be used to broadcast
    | events over WebSockets or other systems.
    |
    */

    'connections' => [

        'reverb' => [
            'driver' => 'reverb',
            'app_id' => env('REVERB_APP_ID', '301724'),
            'key' => env('REVERB_APP_KEY', 'ab7fs1iqb0w0yuu0u5x7'),
            'secret' => env('REVERB_APP_SECRET', 'qfi4egzm5qrozf5kqcxz'),
            'options' => [
                'host' => env('REVERB_HOST', 'localhost'),
                'port' => env('REVERB_PORT', 8080),
                'scheme' => env('REVERB_SCHEME', 'http'),
                // Automatically set useTLS based on scheme
                'useTLS' => env('REVERB_SCHEME', 'http') === 'https',
            ],
            'client_options' => [
                // Optional: Guzzle options, e.g. timeout, verify SSL
                // 'timeout' => 5,
                // 'verify' => false,
            ],
        ],

        // Optional logging driver (good for debugging)
        'log' => [
            'driver' => 'log',
        ],

        // Null driver (no broadcasting)
        'null' => [
            'driver' => 'null',
        ],
    ],
];

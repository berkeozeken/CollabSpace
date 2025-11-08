<?php

return [

    'default' => env('BROADCAST_DRIVER', 'null'),

    'connections' => [

        'pusher' => [
            'driver'  => 'pusher',
            'key'     => env('PUSHER_APP_KEY'),
            'secret'  => env('PUSHER_APP_SECRET'),
            'app_id'  => env('PUSHER_APP_ID'),
            // Güvenli options: Cloud için host/port/scheme KULLANMA.
            // Eğer env'de üçü de DOLU ise (lokal WS), otomatik eklenir.
            'options' => (function () {
                $opts = [
                    'cluster' => env('PUSHER_APP_CLUSTER', 'eu'),
                    'useTLS'  => true,
                ];

                $host   = env('PUSHER_HOST');
                $port   = env('PUSHER_PORT');
                $scheme = env('PUSHER_SCHEME');

                if ($host && $port && $scheme) {
                    $opts['host']   = $host;
                    $opts['port']   = (int) $port;
                    $opts['scheme'] = $scheme;
                    // scheme https ise TLS zorunlu, http ise kapat
                    $opts['useTLS'] = ($scheme === 'https');
                }

                return $opts;
            })(),
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

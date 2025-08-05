<?php

return [
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'reset-password',
        'forgot-password',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['http://localhost:3000',
    'http://127.0.0.1:3000',],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Content-Disposition'],

    'max_age' => 0,

    'supports_credentials' => true,
];

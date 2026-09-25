<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'storage/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        // Add your production domain here, e.g. 'https://yourstore.com'
    ],
    'allowed_origins_patterns' => [
        '/^https:\/\/.*\.ngrok-free\.dev$/',
        '/^https:\/\/.*\.ngrok-free\.app$/',
        '/^http:\/\/localhost:\d+$/',
        '/^http:\/\/127\.0\.0\.1:\d+$/',
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];

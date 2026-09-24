<?php

return [
    'name' => 'RiskWeft',

    // Derived from APP_ENV rather than a separate APP_DEBUG business-config
    // key, so debug state can never silently diverge from the documented
    // environment value in contracts/config.schema.json.
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', env('APP_ENV') !== 'production'),
    'url' => env('APP_URL', 'http://localhost'),

    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',

    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => [],

    'maintenance' => [
        'driver' => 'file',
    ],
];

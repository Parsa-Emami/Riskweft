<?php

return [
    'default' => env('CACHE_STORE', 'redis'),

    'stores' => [
        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'lock_connection' => 'default',
        ],
    ],

    'prefix' => env('SERVICE_NAME', 'riskweft').'_cache_',

    // Laravel 13 hardening default against deserialization gadget-chain
    // attacks if APP_KEY ever leaks. RiskWeft never caches PHP objects
    // (only scalars/arrays, and nothing in Phase 0 uses the cache at all
    // yet), so the safe default is to allow none rather than allow-list
    // specific classes.
    'serializable_classes' => false,
];

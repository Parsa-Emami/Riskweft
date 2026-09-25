<?php

return [
    // Never actually engaged: no route in routes/api.php uses the 'web'
    // middleware group or session-backed auth (contracts/openapi.v1.yaml
    // is bearer-token only, config/sanctum.php 'stateful' => []). This file
    // exists purely so config('session.*') resolves to real values instead
    // of null if any framework/package boot path reads it, rather than
    // because RiskWeft's API itself uses sessions.
    'driver' => env('SESSION_DRIVER', 'array'),

    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'expire_on_close' => false,

    'encrypt' => false,

    'files' => storage_path('framework/sessions'),

    'connection' => null,

    'table' => 'sessions',

    'store' => null,

    'lottery' => [2, 100],

    'cookie' => env('SERVICE_NAME', 'riskweft').'-session',

    'path' => '/',

    'domain' => env('SESSION_DOMAIN'),

    'secure' => env('SESSION_SECURE_COOKIE'),

    'http_only' => true,

    'same_site' => 'lax',

    'partitioned' => false,
];

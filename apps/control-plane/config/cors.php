<?php

return [
    'paths' => ['api/*', 'up'],
    'allowed_methods' => ['GET', 'POST', 'PATCH', 'DELETE'],

    // No wildcard origin: the canvas web app origin is added explicitly once
    // Phase 1 defines it. An empty allow-list fails closed rather than open.
    'allowed_origins' => array_filter(explode(',', (string) env('RISKWEFT_CORS_ALLOWED_ORIGINS', ''))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'Authorization', 'Idempotency-Key', 'X-Request-ID'],
    'exposed_headers' => ['X-Request-ID'],
    'max_age' => 0,
    'supports_credentials' => false,
];

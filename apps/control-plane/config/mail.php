<?php

return [
    // No endpoint in Phase 0 sends mail. This exists so config('mail.*')
    // resolves to real values if any framework/package boot path reads it;
    // 'array' in testing captures mail in memory instead of sending it.
    'default' => env('MAIL_MAILER', 'array'),

    'mailers' => [
        'array' => [
            'transport' => 'array',
        ],

        'log' => [
            'transport' => 'log',
            'channel' => null,
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@riskweft.invalid'),
        'name' => env('MAIL_FROM_NAME', 'RiskWeft'),
    ],
];

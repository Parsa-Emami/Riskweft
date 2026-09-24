<?php

use Laravel\Sanctum\Sanctum;

return [
    // Stateful/cookie-session guards are not used: RiskWeft's API is a
    // bearer-token-only control plane (see contracts/openapi.v1.yaml
    // securitySchemes.bearerAuth). Sanctum personal access tokens are the
    // v1 local/dev-issued bearer credential; a production OIDC access-token
    // verifier is an infrastructure-phase concern (see
    // app/Console/Commands/SeedDevActorCommand.php and
    // docs/phase-0-exit-evidence.md).
    'stateful' => [],

    'guard' => ['web'],

    'expiration' => null,

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];

<?php

declare(strict_types=1);

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Laravel\Sanctum\Http\Middleware\AuthenticateSession;

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
        'authenticate_session' => AuthenticateSession::class,
        'encrypt_cookies' => EncryptCookies::class,
        // Laravel 13 renamed VerifyCsrfToken -> PreventRequestForgery (adds
        // Sec-Fetch-Site origin verification on top of token validation).
        // Dead code in practice for this app - 'stateful' => [] above means
        // Sanctum never applies its stateful/cookie CSRF-protecting
        // middleware group - kept current anyway rather than referencing
        // the deprecated alias.
        'validate_csrf_token' => PreventRequestForgery::class,
    ],
];

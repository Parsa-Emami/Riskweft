<?php

use App\Http\Middleware\AssignRequestCorrelationId;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\RequireIdempotencyKey;
use App\Http\Support\ApiExceptionRenderer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            AssignRequestCorrelationId::class,
            ForceJsonResponse::class,
        ]);

        $middleware->alias([
            'idempotent' => RequireIdempotencyKey::class,
        ]);

        // API is stateless bearer-token only; no CSRF/session middleware group is attached.
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        ApiExceptionRenderer::register($exceptions);
    })
    ->create();

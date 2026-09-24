<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces the *shape* of contracts/openapi.v1.yaml parameters.IdempotencyKey
 * (required, 16-128 chars) before the request reaches the controller. The
 * actual replay-or-execute semantics live in
 * App\Domain\Ports\IdempotencyStoreInterface, invoked from the controller,
 * because that needs the parsed request body (to fingerprint it) which this
 * early middleware stage does not concern itself with.
 */
final class RequireIdempotencyKey
{
    private const MIN_LENGTH = 16;

    private const MAX_LENGTH = 128;

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');

        if (! is_string($key) || strlen($key) < self::MIN_LENGTH || strlen($key) > self::MAX_LENGTH) {
            return ApiResponse::error(
                requestId: (string) $request->attributes->get('correlation_id'),
                status: 422,
                code: 'IDEMPOTENCY_KEY_REQUIRED',
                message: 'A valid Idempotency-Key header ('.self::MIN_LENGTH.'-'.self::MAX_LENGTH.' characters) is required for this operation.',
                retryable: false,
            );
        }

        return $next($request);
    }
}

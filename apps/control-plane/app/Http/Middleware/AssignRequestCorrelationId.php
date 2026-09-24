<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Shared\Uuid;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every request gets a correlation id: the client's X-Request-ID
 * (contracts/openapi.v1.yaml parameters.RequestId) if it supplies one and it
 * is well-formed, otherwise a freshly generated one. It is echoed back on
 * the response and is what OperationResult.request_id / ApiError.request_id
 * carry, and what ties together the HTTP entry, the outbox row and the
 * audit row for one operation (spec/07_OBSERVABILITY_CATALOG.md "Trace
 * contract").
 */
final class AssignRequestCorrelationId
{
    public function handle(Request $request, Closure $next): Response
    {
        $incoming = $request->header('X-Request-ID');
        $correlationId = (is_string($incoming) && strlen($incoming) >= 8 && strlen($incoming) <= 128)
            ? $incoming
            : Uuid::generate()->toString();

        $request->attributes->set('correlation_id', $correlationId);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-Request-ID', $correlationId);

        return $response;
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Support;

use App\Domain\Architecture\Exceptions\InvalidArchitectureDocumentException;
use App\Domain\Shared\DomainException;
use App\Domain\Shared\Uuid;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

/**
 * Every response this API sends - success or failure - uses the envelope in
 * contracts/openapi.v1.yaml. This is the one place that turns "something
 * went wrong" into that envelope, so a client integrating against the
 * OpenAPI contract never sees Laravel's default HTML/plain error page or an
 * un-enveloped JSON shape, and a raw exception message/stack trace never
 * reaches a response body (spec/07_OBSERVABILITY_CATALOG.md "no credentials,
 * plaintext secrets, raw tokens... in logs" applies equally to responses).
 */
final class ApiExceptionRenderer
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->renderable(function (Throwable $e, Request $request) {
            $requestId = self::requestId($request);

            if ($e instanceof DomainException) {
                return ApiResponse::error(
                    $requestId,
                    $e->httpStatus(),
                    $e->errorCode(),
                    $e->getMessage(),
                    $e->isRetryable(),
                    $e instanceof InvalidArchitectureDocumentException ? ['violations' => $e->violations()] : null,
                );
            }

            if ($e instanceof ValidationException) {
                return ApiResponse::error($requestId, 422, 'VALIDATION_FAILED', 'The given data was invalid.', false, $e->errors());
            }

            if ($e instanceof AuthenticationException) {
                return ApiResponse::error($requestId, 401, 'UNAUTHENTICATED', 'Authentication is required.', false);
            }

            if ($e instanceof AuthorizationException) {
                return ApiResponse::error($requestId, 403, 'FORBIDDEN', 'This action is unauthorized.', false);
            }

            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return ApiResponse::error($requestId, 404, 'NOT_FOUND', 'The requested resource was not found.', false);
            }

            if ($e instanceof TooManyRequestsHttpException) {
                return ApiResponse::error($requestId, 429, 'RATE_LIMITED', 'Too many requests.', true);
            }

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                if ($status >= 400 && $status < 500) {
                    return ApiResponse::error($requestId, $status, 'REQUEST_REJECTED', 'The request could not be processed.', false);
                }
            }

            // Anything else is an unexpected server error: never leak $e->getMessage()/trace.
            return ApiResponse::error($requestId, 500, 'INTERNAL_ERROR', 'An unexpected error occurred.', false);
        });
    }

    private static function requestId(Request $request): string
    {
        $fromMiddleware = $request->attributes->get('correlation_id');

        return is_string($fromMiddleware) && $fromMiddleware !== '' ? $fromMiddleware : Uuid::generate()->toString();
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/**
 * Base type for every exception raised inside the Domain layer.
 *
 * Domain objects do not import Laravel/Eloquent/vendor SDKs
 * (spec/01_DOMAIN_CATALOG.md - "Aggregate rules"), so this extends the SPL
 * base exception only. The HTTP layer (App\Http\Support\ApiExceptionRenderer)
 * is responsible for translating domain exceptions into the stable ApiError
 * envelope defined in contracts/openapi.v1.yaml; it never leaks a raw
 * exception message to a client.
 */
abstract class DomainException extends \RuntimeException
{
    /**
     * Stable, machine-readable error code matching ^[A-Z0-9_]+$
     * (contracts/openapi.v1.yaml components.schemas.ApiError.code).
     */
    abstract public function errorCode(): string;

    /**
     * The HTTP status this maps to. Declared on the exception itself (not a
     * central switch in the HTTP layer) so the mapping can never drift from
     * the exception's own meaning, and so every status this codebase can
     * emit stays inside the set contracts/openapi.v1.yaml declares for each
     * route (400/401/403/404/409/422/429/500).
     */
    abstract public function httpStatus(): int;

    /** Whether retrying the exact same request could plausibly succeed. */
    public function isRetryable(): bool
    {
        return false;
    }
}

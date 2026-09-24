<?php

declare(strict_types=1);

namespace App\Domain\Ports;

use App\Domain\Shared\DomainException;

/** The same Idempotency-Key header was reused for a materially different request. */
final class IdempotencyKeyConflictException extends DomainException
{
    public function __construct(public readonly string $key)
    {
        parent::__construct("Idempotency-Key [{$key}] was already used for a different request.");
    }

    public function errorCode(): string
    {
        return 'IDEMPOTENCY_KEY_CONFLICT';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    public function isRetryable(): bool
    {
        // Retrying with a *new* Idempotency-Key is fine; retrying with the
        // same key+body is exactly what this rejects, so the flag describes
        // the request as given.
        return false;
    }
}

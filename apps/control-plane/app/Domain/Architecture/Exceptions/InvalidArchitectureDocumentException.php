<?php

declare(strict_types=1);

namespace App\Domain\Architecture\Exceptions;

use App\Domain\Shared\DomainException;

/**
 * Raised when a submitted architecture document violates a structural
 * invariant (duplicate id, dangling reference, reserved attribute key,
 * oversized payload, ...). Carries every violation found, not just the
 * first, so a single round trip is enough for a client to fix its payload.
 */
final class InvalidArchitectureDocumentException extends DomainException
{
    /** @param list<string> $violations */
    public function __construct(private readonly array $violations)
    {
        parent::__construct('Architecture document failed validation: '.implode('; ', $violations));
    }

    /** @return list<string> */
    public function violations(): array
    {
        return $this->violations;
    }

    public function errorCode(): string
    {
        return 'ARCHITECTURE_DOCUMENT_INVALID';
    }

    public function httpStatus(): int
    {
        return 422;
    }
}

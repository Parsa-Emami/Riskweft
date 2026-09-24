<?php

declare(strict_types=1);

namespace App\Domain\Architecture\Exceptions;

use App\Domain\Shared\DomainException;

final class ArchitectureRevisionNotFoundException extends DomainException
{
    public function __construct(public readonly string $revisionId)
    {
        parent::__construct("Architecture revision not found: [{$revisionId}].");
    }

    public function errorCode(): string
    {
        return 'REVISION_NOT_FOUND';
    }

    public function httpStatus(): int
    {
        return 404;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Architecture\Exceptions;

use App\Domain\Shared\DomainException;

final class ProjectNotFoundException extends DomainException
{
    public function __construct(public readonly string $projectId)
    {
        parent::__construct("Project not found: [{$projectId}].");
    }

    public function errorCode(): string
    {
        return 'PROJECT_NOT_FOUND';
    }

    public function httpStatus(): int
    {
        return 404;
    }
}

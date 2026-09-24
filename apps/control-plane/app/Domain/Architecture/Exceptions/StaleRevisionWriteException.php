<?php

declare(strict_types=1);

namespace App\Domain\Architecture\Exceptions;

use App\Domain\Shared\DomainException;

/**
 * The client's base_revision_id no longer matches the project's current
 * head revision: someone else committed first. Maps to the "stale revision
 * write" entry in implementation-start/failure-catalog.json and E2E
 * scenario 4 in spec/10_E2E_ACCEPTANCE_SCENARIOS.md ("Concurrent stale edit
 * returns conflict not overwrite"). The caller must re-read the head
 * revision and retry - retrying with the same stale base would fail again,
 * so this is NOT retryable as-is.
 */
final class StaleRevisionWriteException extends DomainException
{
    public function __construct(
        public readonly string $projectId,
        public readonly ?string $expectedBaseRevisionId,
        public readonly ?string $actualHeadRevisionId,
    ) {
        parent::__construct(
            "Stale base revision for project [{$projectId}]: expected head [".
            ($expectedBaseRevisionId ?? 'null')."], actual head [".($actualHeadRevisionId ?? 'null').'].'
        );
    }

    public function errorCode(): string
    {
        return 'REVISION_CONFLICT';
    }

    public function httpStatus(): int
    {
        return 409;
    }
}

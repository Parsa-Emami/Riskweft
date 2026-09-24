<?php

declare(strict_types=1);

namespace App\Domain\Ports;

use App\Domain\Architecture\ArchitectureDocument;
use App\Domain\Architecture\ArchitectureRevision;
use App\Domain\Architecture\ContentHash;
use App\Domain\Architecture\Exceptions\ProjectNotFoundException;
use App\Domain\Architecture\Exceptions\StaleRevisionWriteException;

interface ArchitectureRevisionRepositoryInterface
{
    /**
     * Atomically: verify $expectedBaseRevisionId is still the project's
     * current head, persist the new revision (canonical document + typed
     * entity/relationship rows), advance the project's head pointer, and
     * write the outbox + audit rows - all in one database transaction, so
     * "commit" either fully happens or leaves no partial trace
     * (COMPONENT_ARCHITECTURE_V7.md "transactional outbox... are
     * mandatory").
     *
     * $expectedBaseRevisionId is null only for a project's first revision.
     *
     * @throws StaleRevisionWriteException the project's head moved since
     *         the caller last read it - implementation-start/failure-catalog.json
     *         "stale revision write".
     * @throws ProjectNotFoundException
     */
    public function commit(
        string $revisionId,
        string $projectId,
        ?string $expectedBaseRevisionId,
        ArchitectureDocument $document,
        ContentHash $contentHash,
        ?string $committedByUserId,
        \DateTimeImmutable $committedAt,
        string $correlationId,
    ): ArchitectureRevision;

    public function find(string $revisionId): ?ArchitectureRevision;
}

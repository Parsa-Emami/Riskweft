<?php

declare(strict_types=1);

namespace App\Domain\Architecture;

/**
 * An immutable, committed ArchitectureRevision (Frozen v1 Invariant #1:
 * "ArchitectureRevision is immutable once committed").
 *
 * This object never mutates after construction - there is deliberately no
 * setter, no status-transition method, nothing. Building one is the
 * responsibility of App\Application\Architecture\CommitArchitectureRevisionHandler
 * (which canonicalizes + hashes the document) together with the
 * Infrastructure repository (which assigns the project-scoped sequence
 * number transactionally and persists it) - never a controller or an
 * Eloquent model directly (spec/01_DOMAIN_CATALOG.md "Aggregate rules").
 */
final class ArchitectureRevision
{
    /**
     * contracts/openapi.v1.yaml requires `base_revision_id` on every commit
     * request (required, minLength 1) with no null/absent case documented,
     * so a project's first-ever revision has no real predecessor to name.
     * This sentinel is what a client sends in that case; the HTTP layer
     * translates it to a real `null` base before it ever reaches the
     * Application/Domain layers. Recorded as a candidate for its own ADR if
     * it needs to become fully official beyond this phase.
     */
    public const string GENESIS_BASE_REVISION_ID = 'genesis';

    public function __construct(
        public readonly string $id,
        public readonly string $projectId,
        public readonly ?string $parentRevisionId,
        public readonly int $sequenceNo,
        public readonly RevisionStatus $status,
        public readonly ArchitectureDocument $document,
        public readonly ContentHash $contentHash,
        public readonly ?string $committedByUserId,
        public readonly \DateTimeImmutable $committedAt,
    ) {}
}

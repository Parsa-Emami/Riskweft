<?php

declare(strict_types=1);

namespace App\Application\Architecture;

use App\Domain\Architecture\ArchitectureDocumentValidator;
use App\Domain\Architecture\ArchitectureRevision;
use App\Domain\Architecture\Canonicalizer;
use App\Domain\Architecture\ContentHash;
use App\Domain\Ports\ArchitectureRevisionRepositoryInterface;
use App\Domain\Shared\ClockInterface;
use App\Domain\Shared\Uuid;

/**
 * Orchestrates the one Phase 0 write use case: validate -> canonicalize ->
 * hash -> commit-with-optimistic-concurrency. This is the *only* place
 * allowed to perform this mutation (spec/01_DOMAIN_CATALOG.md "Aggregate
 * mutations occur through application commands, never controller/model
 * convenience writes") - App\Http\Controllers\Api\V1\ArchitectureRevisionController
 * does nothing but adapt HTTP <-> this handler.
 */
final class CommitArchitectureRevisionHandler
{
    public function __construct(
        private readonly ArchitectureRevisionRepositoryInterface $revisions,
        private readonly ClockInterface $clock,
    ) {}

    public function handle(CommitArchitectureRevisionCommand $command): ArchitectureRevision
    {
        $document = ArchitectureDocumentValidator::validate(
            $command->rawArchitecture,
            (int) config('riskweft.max_architecture_bytes'),
        );

        $canonicalJson = Canonicalizer::canonicalize($document);
        $contentHash = ContentHash::compute($canonicalJson, (string) config('riskweft.hash_algorithm'));

        return $this->revisions->commit(
            revisionId: Uuid::generate()->toString(),
            projectId: $command->projectId,
            expectedBaseRevisionId: $command->baseRevisionId,
            document: $document,
            contentHash: $contentHash,
            committedByUserId: $command->actingUserId,
            committedAt: $this->clock->now(),
            correlationId: $command->correlationId,
        );
    }
}

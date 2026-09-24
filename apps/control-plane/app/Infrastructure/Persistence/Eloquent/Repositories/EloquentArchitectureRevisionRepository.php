<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Architecture\ArchitectureDocument;
use App\Domain\Architecture\ArchitectureEntity;
use App\Domain\Architecture\ArchitectureRelationship;
use App\Domain\Architecture\ArchitectureRevision;
use App\Domain\Architecture\ContentHash;
use App\Domain\Architecture\Exceptions\ProjectNotFoundException;
use App\Domain\Architecture\Exceptions\StaleRevisionWriteException;
use App\Domain\Architecture\RevisionStatus;
use App\Domain\Ports\ArchitectureRevisionRepositoryInterface;
use App\Domain\Ports\AuditEntry;
use App\Domain\Ports\AuditLoggerInterface;
use App\Domain\Ports\OutboxMessage;
use App\Domain\Ports\OutboxPublisherInterface;
use App\Domain\Shared\Uuid;
use App\Infrastructure\Persistence\Eloquent\Models\ArchitectureRevisionModel;
use App\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use App\Infrastructure\Persistence\Eloquent\Models\RevisionEntityModel;
use App\Infrastructure\Persistence\Eloquent\Models\RevisionRelationshipModel;
use Illuminate\Support\Facades\DB;

/**
 * Implements optimistic concurrency via a row lock on the owning project for
 * the duration of the transaction (SELECT ... FOR UPDATE): a second,
 * concurrent commit() for the same project blocks until the first finishes,
 * then observes the now-advanced head and fails with
 * StaleRevisionWriteException instead of silently overwriting anything
 * (spec/10_E2E_ACCEPTANCE_SCENARIOS.md scenario 4). This is the Phase 0
 * "optimistic concurrency/version preconditions" deliverable: optimistic
 * from the *caller's* point of view (no client-visible locking protocol,
 * just a base_revision_id precondition), implemented with a short-lived
 * pessimistic lock at the storage boundary - Final Engine Decision #3
 * explicitly scopes this to single-writer-wins semantics, not CRDT merge.
 */
final class EloquentArchitectureRevisionRepository implements ArchitectureRevisionRepositoryInterface
{
    public function __construct(
        private readonly AuditLoggerInterface $auditLogger,
        private readonly OutboxPublisherInterface $outbox,
    ) {}

    public function commit(
        string $revisionId,
        string $projectId,
        ?string $expectedBaseRevisionId,
        ArchitectureDocument $document,
        ContentHash $contentHash,
        ?string $committedByUserId,
        \DateTimeImmutable $committedAt,
        string $correlationId,
    ): ArchitectureRevision {
        return DB::transaction(function () use (
            $revisionId, $projectId, $expectedBaseRevisionId, $document,
            $contentHash, $committedByUserId, $committedAt, $correlationId,
        ) {
            /** @var ProjectModel|null $project */
            $project = ProjectModel::whereKey($projectId)->lockForUpdate()->first();

            if ($project === null) {
                throw new ProjectNotFoundException($projectId);
            }

            $currentHead = $project->head_revision_id;

            if ($expectedBaseRevisionId !== $currentHead) {
                throw new StaleRevisionWriteException($projectId, $expectedBaseRevisionId, $currentHead);
            }

            $nextSequence = $project->head_version + 1;

            ArchitectureRevisionModel::create([
                'id' => $revisionId,
                'project_id' => $projectId,
                'parent_revision_id' => $expectedBaseRevisionId,
                'sequence_no' => $nextSequence,
                'status' => RevisionStatus::Draft,
                'canonical_document' => $document->toCanonicalArray(),
                'content_hash' => $contentHash->toString(),
                'hash_algorithm' => $contentHash->algorithm(),
                'entity_count' => $document->entityCount(),
                'relationship_count' => $document->relationshipCount(),
                'committed_by_user_id' => $committedByUserId,
                'committed_at' => $committedAt,
            ]);

            $entityRowIdByLogicalId = [];
            foreach ($document->entities as $entity) {
                $row = RevisionEntityModel::create([
                    'revision_id' => $revisionId,
                    'entity_id' => $entity->id,
                    'kind' => $entity->kind,
                    'name' => $entity->name,
                    'description' => $entity->description,
                    'attributes' => $entity->attributes,
                ]);
                $entityRowIdByLogicalId[$entity->id] = $row->id;
            }

            foreach ($document->relationships as $relationship) {
                RevisionRelationshipModel::create([
                    'revision_id' => $revisionId,
                    'relationship_id' => $relationship->id,
                    'kind' => $relationship->kind,
                    'source_entity_row_id' => $entityRowIdByLogicalId[$relationship->sourceEntityId],
                    'target_entity_row_id' => $entityRowIdByLogicalId[$relationship->targetEntityId],
                    'label' => $relationship->label,
                    'attributes' => $relationship->attributes,
                ]);
            }

            $project->head_revision_id = $revisionId;
            $project->head_version = $nextSequence;
            $project->save();

            // Outbox + audit rows are written in this same transaction, so a
            // failure anywhere above means neither is ever visible
            // (COMPONENT_ARCHITECTURE_V7.md "transactional outbox... mandatory").
            $this->outbox->record(new OutboxMessage(
                eventId: Uuid::generate()->toString(),
                eventType: 'architecture.revision_committed.v1',
                aggregateType: 'architecture_revision',
                aggregateId: $revisionId,
                correlationId: $correlationId,
                causationId: null,
                payload: [
                    'project_id' => $projectId,
                    'revision_id' => $revisionId,
                    'sequence_no' => $nextSequence,
                    'content_hash' => $contentHash->toString(),
                    'entity_count' => $document->entityCount(),
                    'relationship_count' => $document->relationshipCount(),
                ],
                occurredAt: $committedAt,
            ));

            $this->auditLogger->record(new AuditEntry(
                actorUserId: $committedByUserId,
                action: 'architecture_revision.committed',
                resourceType: 'architecture_revision',
                resourceId: $revisionId,
                projectId: $projectId,
                correlationId: $correlationId,
                metadata: [
                    'sequence_no' => $nextSequence,
                    'content_hash' => $contentHash->toString(),
                    'entity_count' => $document->entityCount(),
                    'relationship_count' => $document->relationshipCount(),
                ],
                occurredAt: $committedAt,
            ));

            return new ArchitectureRevision(
                id: $revisionId,
                projectId: $projectId,
                parentRevisionId: $expectedBaseRevisionId,
                sequenceNo: $nextSequence,
                status: RevisionStatus::Draft,
                document: $document,
                contentHash: $contentHash,
                committedByUserId: $committedByUserId,
                committedAt: $committedAt,
            );
        });
    }

    public function find(string $revisionId): ?ArchitectureRevision
    {
        if (! Uuid::isValid($revisionId)) {
            return null;
        }

        /** @var ArchitectureRevisionModel|null $row */
        $row = ArchitectureRevisionModel::find($revisionId);

        if ($row === null) {
            return null;
        }

        return new ArchitectureRevision(
            id: $row->id,
            projectId: $row->project_id,
            parentRevisionId: $row->parent_revision_id,
            sequenceNo: $row->sequence_no,
            status: $row->status,
            document: $this->hydrateDocument($row->canonical_document),
            contentHash: ContentHash::fromString($row->content_hash),
            committedByUserId: $row->committed_by_user_id,
            committedAt: $row->committed_at,
        );
    }

    /** @param array<string, mixed> $canonical */
    private function hydrateDocument(array $canonical): ArchitectureDocument
    {
        $entities = array_map(
            static fn (array $e): ArchitectureEntity => ArchitectureEntity::fromArray($e),
            $canonical['entities'] ?? [],
        );

        $relationships = array_map(
            static fn (array $r): ArchitectureRelationship => ArchitectureRelationship::fromArray($r),
            $canonical['relationships'] ?? [],
        );

        return new ArchitectureDocument(
            name: (string) ($canonical['metadata']['name'] ?? ''),
            description: $canonical['metadata']['description'] ?? null,
            entities: $entities,
            relationships: $relationships,
        );
    }
}

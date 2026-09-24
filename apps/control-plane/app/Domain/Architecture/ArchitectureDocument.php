<?php

declare(strict_types=1);

namespace App\Domain\Architecture;

/**
 * The canonical architecture document: the full, order-independent content
 * of one ArchitectureRevision. This is the thing that gets normalized,
 * hashed and stored as the immutable source of truth (Final Engine
 * Decision #1). Canvas coordinates / layout are never part of this object
 * (Frozen v1 Invariant #2 - they are Phase 1's projection, not semantics).
 *
 * Construct via ArchitectureDocumentValidator::validate(), which is the only
 * place allowed to accept raw client input; by the time a document reaches
 * this constructor it is already structurally valid.
 */
final class ArchitectureDocument
{
    public const SCHEMA_VERSION = 1;

    /**
     * @param  list<ArchitectureEntity>  $entities
     * @param  list<ArchitectureRelationship>  $relationships
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly array $entities,
        public readonly array $relationships,
    ) {}

    /**
     * The exact structure that is JSON-encoded and hashed. Array order here
     * is irrelevant to the *input*: entities/relationships are explicitly
     * re-sorted by their stable id so two documents built from the same
     * logical content always canonicalize identically regardless of the
     * order the client submitted them in (Phase 0 Definition of Done).
     *
     * @return array<string, mixed>
     */
    public function toCanonicalArray(): array
    {
        $entities = $this->entities;
        usort($entities, static fn (ArchitectureEntity $a, ArchitectureEntity $b): int => $a->id <=> $b->id);

        $relationships = $this->relationships;
        usort(
            $relationships,
            static fn (ArchitectureRelationship $a, ArchitectureRelationship $b): int => $a->id <=> $b->id
        );

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'metadata' => [
                'name' => $this->name,
                'description' => $this->description,
            ],
            'entities' => array_map(static fn (ArchitectureEntity $e): array => $e->toCanonicalArray(), $entities),
            'relationships' => array_map(
                static fn (ArchitectureRelationship $r): array => $r->toCanonicalArray(),
                $relationships
            ),
        ];
    }

    public function entityCount(): int
    {
        return count($this->entities);
    }

    public function relationshipCount(): int
    {
        return count($this->relationships);
    }

    public function findEntity(string $entityId): ?ArchitectureEntity
    {
        foreach ($this->entities as $entity) {
            if ($entity->id === $entityId) {
                return $entity;
            }
        }

        return null;
    }
}

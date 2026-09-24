<?php

declare(strict_types=1);

namespace App\Domain\Architecture;

/**
 * An edge in the canonical architecture document: a DataFlow between two
 * entities, or a TrustBoundaryMembership placing one entity inside another
 * (spec/01_DOMAIN_CATALOG.md `DataFlow`; RelationshipKind doc comment).
 */
final class ArchitectureRelationship
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public readonly string $id,
        public readonly RelationshipKind $kind,
        public readonly string $sourceEntityId,
        public readonly string $targetEntityId,
        public readonly ?string $label,
        public readonly array $attributes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            kind: RelationshipKind::from((string) ($data['kind'] ?? '')),
            sourceEntityId: (string) ($data['source_entity_id'] ?? ''),
            targetEntityId: (string) ($data['target_entity_id'] ?? ''),
            label: isset($data['label']) && $data['label'] !== '' ? trim((string) $data['label']) : null,
            attributes: is_array($data['attributes'] ?? null) ? $data['attributes'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toCanonicalArray(): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind->value,
            'source_entity_id' => $this->sourceEntityId,
            'target_entity_id' => $this->targetEntityId,
            'label' => $this->label,
            'attributes' => $this->attributes,
        ];
    }
}

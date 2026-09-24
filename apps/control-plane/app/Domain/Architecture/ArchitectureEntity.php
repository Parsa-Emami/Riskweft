<?php

declare(strict_types=1);

namespace App\Domain\Architecture;

/**
 * A node in the canonical architecture document: a Component, TrustBoundary
 * or Asset (spec/01_DOMAIN_CATALOG.md).
 *
 * `id` is the client-supplied *stable logical* identifier: it is expected to
 * stay the same for "the same real-world thing" across multiple revisions,
 * which is what makes Phase 4's semantic diff/merge possible later (Final
 * Engine Decision #4). It is unrelated to any database row id.
 *
 * `attributes` is an arbitrary JSON-safe map of kind-specific fields. Phase 0
 * intentionally does not lock down a per-kind sub-schema (that is rule-pack
 * and UI-driven work from Phase 2 onward); it only enforces the
 * structural bounds needed to keep the canonical form well-defined and safe
 * (see ArchitectureDocumentValidator).
 */
final class ArchitectureEntity
{
    /**
     * @param  array<string, mixed>  $attributes  JSON-safe scalars/arrays/maps only (no floats, no resources).
     */
    public function __construct(
        public readonly string $id,
        public readonly EntityKind $kind,
        public readonly string $name,
        public readonly ?string $description,
        public readonly array $attributes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            kind: EntityKind::from((string) ($data['kind'] ?? '')),
            name: trim((string) ($data['name'] ?? '')),
            description: isset($data['description']) && $data['description'] !== ''
                ? trim((string) $data['description'])
                : null,
            attributes: is_array($data['attributes'] ?? null) ? $data['attributes'] : [],
        );
    }

    /**
     * The exact shape that participates in canonicalization/hashing.
     * Key order here does not matter - Canonicalizer sorts keys recursively.
     *
     * @return array<string, mixed>
     */
    public function toCanonicalArray(): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind->value,
            'name' => $this->name,
            'description' => $this->description,
            'attributes' => $this->attributes,
        ];
    }
}

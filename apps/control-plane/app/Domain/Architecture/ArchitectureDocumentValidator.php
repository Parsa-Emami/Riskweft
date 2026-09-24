<?php

declare(strict_types=1);

namespace App\Domain\Architecture;

use App\Domain\Architecture\Exceptions\InvalidArchitectureDocumentException;
use App\Domain\Shared\Uuid;

/**
 * The only supported way to turn raw, untrusted array data (already decoded
 * from JSON by the HTTP layer) into a valid ArchitectureDocument.
 *
 * This is where every structural invariant from the spec is enforced in one
 * place, collecting *all* violations rather than failing on the first one:
 *
 *  - spec/01_DOMAIN_CATALOG.md Frozen v1 invariant #2: canvas coordinates
 *    are presentation metadata and cannot change security semantics, so a
 *    handful of layout-shaped attribute keys are rejected outright rather
 *    than silently accepted and ignored.
 *  - implementation-start/failure-catalog.json "dangling component
 *    reference": every relationship endpoint must reference an entity id
 *    present in the same document.
 *  - spec/09_SECURITY_ABUSE_TESTS.md "zip/entity expansion": the document
 *    is bounded in entity/relationship count and attribute nesting depth
 *    before it is ever canonicalized or persisted.
 */
final class ArchitectureDocumentValidator
{
    /**
     * Safety bounds. These are operational abuse-resistance limits, not
     * part of the documented business config surface
     * (contracts/config.schema.json) - promote to a config key via ADR if a
     * deployment ever needs them tunable.
     */
    private const MAX_ENTITIES = 5000;

    private const MAX_RELATIONSHIPS = 10000;

    private const MAX_ATTRIBUTE_DEPTH = 6;

    private const MAX_NAME_LENGTH = 255;

    private const MAX_DESCRIPTION_LENGTH = 8000;

    /** Attribute keys that would encode canvas/layout state, which never belongs in the canonical document. */
    private const RESERVED_ATTRIBUTE_KEYS = ['x', 'y', 'position', 'layout', 'canvas', 'coordinates'];

    /**
     * @param  array<string, mixed>  $raw  Already JSON-decoded (associative) request payload.
     *
     * @throws InvalidArchitectureDocumentException
     */
    public static function validate(array $raw, int $maxBytes): ArchitectureDocument
    {
        $violations = [];

        self::checkSize($raw, $maxBytes, $violations);

        $metadata = is_array($raw['metadata'] ?? null) ? $raw['metadata'] : [];
        $name = trim((string) ($metadata['name'] ?? ''));
        $description = isset($metadata['description']) && $metadata['description'] !== ''
            ? trim((string) $metadata['description'])
            : null;

        if ($name === '') {
            $violations[] = 'metadata.name is required.';
        } elseif (mb_strlen($name) > self::MAX_NAME_LENGTH) {
            $violations[] = 'metadata.name exceeds '.self::MAX_NAME_LENGTH.' characters.';
        }

        $rawEntities = is_array($raw['entities'] ?? null) ? $raw['entities'] : [];
        $rawRelationships = is_array($raw['relationships'] ?? null) ? $raw['relationships'] : [];

        if (count($rawEntities) > self::MAX_ENTITIES) {
            $violations[] = 'entities exceeds the maximum of '.self::MAX_ENTITIES.' per revision.';
        }
        if (count($rawRelationships) > self::MAX_RELATIONSHIPS) {
            $violations[] = 'relationships exceeds the maximum of '.self::MAX_RELATIONSHIPS.' per revision.';
        }

        [$entities, $entityIds] = self::buildEntities($rawEntities, $violations);
        $relationships = self::buildRelationships($rawRelationships, $entityIds, $violations);

        if ($violations !== []) {
            throw new InvalidArchitectureDocumentException($violations);
        }

        return new ArchitectureDocument(
            name: $name,
            description: $description,
            entities: $entities,
            relationships: $relationships,
        );
    }

    /**
     * @param  array<string, mixed>  $raw
     * @param  list<string>  $violations
     */
    private static function checkSize(array $raw, int $maxBytes, array &$violations): void
    {
        $encoded = json_encode($raw);
        $bytes = $encoded === false ? PHP_INT_MAX : strlen($encoded);

        if ($bytes > $maxBytes) {
            $violations[] = "document is {$bytes} bytes, exceeding the configured limit of {$maxBytes} bytes.";
        }

        if (self::depth($raw) > self::MAX_ATTRIBUTE_DEPTH + 2) {
            // +2 accounts for the outer {entities:[{attributes:{...}}]} envelope itself.
            $violations[] = 'document nesting exceeds the maximum permitted depth.';
        }
    }

    private static function depth(mixed $value, int $current = 0): int
    {
        if (! is_array($value) || $current > 64) {
            return $current;
        }

        $deepest = $current;
        foreach ($value as $item) {
            $deepest = max($deepest, self::depth($item, $current + 1));
        }

        return $deepest;
    }

    /**
     * @param  list<mixed>  $rawEntities
     * @param  list<string>  $violations
     * @return array{0: list<ArchitectureEntity>, 1: array<string, true>}
     */
    private static function buildEntities(array $rawEntities, array &$violations): array
    {
        $entities = [];
        $seenIds = [];

        foreach ($rawEntities as $index => $rawEntity) {
            if (! is_array($rawEntity)) {
                $violations[] = "entities[{$index}] must be an object.";

                continue;
            }

            $id = (string) ($rawEntity['id'] ?? '');
            if (! Uuid::isValid($id)) {
                $violations[] = "entities[{$index}].id must be a UUID.";

                continue;
            }

            if (isset($seenIds[$id])) {
                $violations[] = "entities[{$index}].id [{$id}] is a duplicate within this document.";

                continue;
            }

            $kindValue = (string) ($rawEntity['kind'] ?? '');
            $kind = EntityKind::tryFrom($kindValue);
            if ($kind === null) {
                $allowed = implode(', ', array_map(fn (EntityKind $k) => $k->value, EntityKind::cases()));
                $violations[] = "entities[{$index}].kind [{$kindValue}] must be one of: {$allowed}.";

                continue;
            }

            $name = trim((string) ($rawEntity['name'] ?? ''));
            if ($name === '') {
                $violations[] = "entities[{$index}].name is required.";

                continue;
            }
            if (mb_strlen($name) > self::MAX_NAME_LENGTH) {
                $violations[] = "entities[{$index}].name exceeds ".self::MAX_NAME_LENGTH.' characters.';

                continue;
            }

            $description = isset($rawEntity['description']) && $rawEntity['description'] !== ''
                ? trim((string) $rawEntity['description'])
                : null;
            if ($description !== null && mb_strlen($description) > self::MAX_DESCRIPTION_LENGTH) {
                $violations[] = "entities[{$index}].description exceeds ".self::MAX_DESCRIPTION_LENGTH.' characters.';

                continue;
            }

            $attributes = is_array($rawEntity['attributes'] ?? null) ? $rawEntity['attributes'] : [];
            $reserved = self::findReservedKeys($attributes);
            if ($reserved !== []) {
                $violations[] = "entities[{$index}].attributes uses reserved layout key(s): ".implode(', ', $reserved).
                    '. Canvas position is presentation metadata and is never part of the canonical document.';

                continue;
            }

            $seenIds[$id] = true;
            $entities[] = new ArchitectureEntity($id, $kind, $name, $description, $attributes);
        }

        return [$entities, $seenIds];
    }

    /**
     * @param  list<mixed>  $rawRelationships
     * @param  array<string, true>  $entityIds
     * @param  list<string>  $violations
     * @return list<ArchitectureRelationship>
     */
    private static function buildRelationships(array $rawRelationships, array $entityIds, array &$violations): array
    {
        $relationships = [];
        $seenIds = [];

        foreach ($rawRelationships as $index => $rawRelationship) {
            if (! is_array($rawRelationship)) {
                $violations[] = "relationships[{$index}] must be an object.";

                continue;
            }

            $id = (string) ($rawRelationship['id'] ?? '');
            if (! Uuid::isValid($id)) {
                $violations[] = "relationships[{$index}].id must be a UUID.";

                continue;
            }

            if (isset($seenIds[$id])) {
                $violations[] = "relationships[{$index}].id [{$id}] is a duplicate within this document.";

                continue;
            }

            $kindValue = (string) ($rawRelationship['kind'] ?? '');
            $kind = RelationshipKind::tryFrom($kindValue);
            if ($kind === null) {
                $allowed = implode(', ', array_map(fn (RelationshipKind $k) => $k->value, RelationshipKind::cases()));
                $violations[] = "relationships[{$index}].kind [{$kindValue}] must be one of: {$allowed}.";

                continue;
            }

            $sourceId = (string) ($rawRelationship['source_entity_id'] ?? '');
            $targetId = (string) ($rawRelationship['target_entity_id'] ?? '');

            if (! isset($entityIds[$sourceId])) {
                $violations[] = "relationships[{$index}].source_entity_id [{$sourceId}] does not reference an entity in this document (dangling reference).";

                continue;
            }
            if (! isset($entityIds[$targetId])) {
                $violations[] = "relationships[{$index}].target_entity_id [{$targetId}] does not reference an entity in this document (dangling reference).";

                continue;
            }
            if ($sourceId === $targetId) {
                $violations[] = "relationships[{$index}] source and target must not be the same entity.";

                continue;
            }

            $label = isset($rawRelationship['label']) && $rawRelationship['label'] !== ''
                ? trim((string) $rawRelationship['label'])
                : null;

            $attributes = is_array($rawRelationship['attributes'] ?? null) ? $rawRelationship['attributes'] : [];
            $reserved = self::findReservedKeys($attributes);
            if ($reserved !== []) {
                $violations[] = "relationships[{$index}].attributes uses reserved layout key(s): ".implode(', ', $reserved).'.';

                continue;
            }

            $seenIds[$id] = true;
            $relationships[] = new ArchitectureRelationship($id, $kind, $sourceId, $targetId, $label, $attributes);
        }

        return $relationships;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return list<string>
     */
    private static function findReservedKeys(array $attributes): array
    {
        return array_values(array_intersect(array_keys($attributes), self::RESERVED_ATTRIBUTE_KEYS));
    }
}

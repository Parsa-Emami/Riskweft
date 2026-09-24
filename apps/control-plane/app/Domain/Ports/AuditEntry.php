<?php

declare(strict_types=1);

namespace App\Domain\Ports;

/**
 * A deliberate, queryable security audit record - distinct from operational
 * logs, which may rotate independently (spec/07_OBSERVABILITY_CATALOG.md
 * "Audit != logs"). `metadata` must never contain secrets, full architecture
 * payloads or other sensitive row content - only identifiers/counts/hashes
 * (spec/07_OBSERVABILITY_CATALOG.md "Logging").
 */
final class AuditEntry
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public readonly ?string $actorUserId,
        public readonly string $action,
        public readonly string $resourceType,
        public readonly string $resourceId,
        public readonly ?string $projectId,
        public readonly string $correlationId,
        public readonly array $metadata,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Domain\Ports;

/**
 * A CloudEvents-shaped domain/integration event, written to outbox_messages
 * in the same transaction as the state change that produced it
 * (contracts/event-types.v1.json envelope; spec/03_EVENT_CATALOG.md).
 * Publishing this row to Valkey/an external broker is a relay worker's job,
 * deliberately out of Phase 0's scope (see docs/phase-0-exit-evidence.md).
 */
final class OutboxMessage
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public readonly string $eventId,
        public readonly string $eventType,
        public readonly string $aggregateType,
        public readonly string $aggregateId,
        public readonly string $correlationId,
        public readonly ?string $causationId,
        public readonly array $payload,
        public readonly \DateTimeImmutable $occurredAt,
    ) {}
}

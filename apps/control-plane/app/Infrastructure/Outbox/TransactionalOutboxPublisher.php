<?php

declare(strict_types=1);

namespace App\Infrastructure\Outbox;

use App\Domain\Ports\OutboxMessage;
use App\Domain\Ports\OutboxPublisherInterface;
use App\Infrastructure\Persistence\Eloquent\Models\OutboxMessageModel;

/**
 * Writes the outbox row only. Relaying unpublished rows to Valkey/an
 * external broker is a separate worker process, deliberately out of scope
 * for Phase 0 (see docs/phase-0-exit-evidence.md "Deferred"). What Phase 0
 * guarantees and tests is the transactional write itself: the row exists
 * if and only if the state change it describes was committed.
 */
final class TransactionalOutboxPublisher implements OutboxPublisherInterface
{
    public function record(OutboxMessage $message): void
    {
        OutboxMessageModel::create([
            'aggregate_type' => $message->aggregateType,
            'aggregate_id' => $message->aggregateId,
            'event_type' => $message->eventType,
            'event_id' => $message->eventId,
            'payload' => $message->payload,
            'correlation_id' => $message->correlationId,
            'causation_id' => $message->causationId,
            'occurred_at' => $message->occurredAt,
            'published_at' => null,
        ]);
    }
}

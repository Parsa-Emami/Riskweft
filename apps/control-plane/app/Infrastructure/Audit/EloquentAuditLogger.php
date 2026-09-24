<?php

declare(strict_types=1);

namespace App\Infrastructure\Audit;

use App\Domain\Ports\AuditEntry;
use App\Domain\Ports\AuditLoggerInterface;
use App\Infrastructure\Persistence\Eloquent\Models\AuditEventModel;

final class EloquentAuditLogger implements AuditLoggerInterface
{
    public function record(AuditEntry $entry): void
    {
        AuditEventModel::create([
            'actor_user_id' => $entry->actorUserId,
            'action' => $entry->action,
            'resource_type' => $entry->resourceType,
            'resource_id' => $entry->resourceId,
            'project_id' => $entry->projectId,
            'correlation_id' => $entry->correlationId,
            'metadata' => $entry->metadata,
            'occurred_at' => $entry->occurredAt,
        ]);
    }
}

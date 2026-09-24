<?php

declare(strict_types=1);

namespace App\Domain\Ports;

interface AuditLoggerInterface
{
    /** Must be called from inside the same DB transaction as the event it records. */
    public function record(AuditEntry $entry): void;
}

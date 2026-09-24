<?php

declare(strict_types=1);

namespace App\Domain\Ports;

interface OutboxPublisherInterface
{
    /** Must be called from inside the same DB transaction as the state change it describes. */
    public function record(OutboxMessage $message): void;
}

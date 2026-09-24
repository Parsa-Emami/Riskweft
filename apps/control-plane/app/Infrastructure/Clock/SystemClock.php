<?php

declare(strict_types=1);

namespace App\Infrastructure\Clock;

use App\Domain\Shared\ClockInterface;

final class SystemClock implements ClockInterface
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}

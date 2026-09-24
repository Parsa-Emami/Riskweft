<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/**
 * A port for "now". Application/handler code asks for the time through this
 * interface instead of calling now()/Carbon::now() directly, so tests can
 * supply a fixed clock and golden/hash-stability assertions are never
 * flaky because of wall-clock time.
 */
interface ClockInterface
{
    public function now(): \DateTimeImmutable;
}

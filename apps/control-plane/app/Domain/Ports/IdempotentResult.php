<?php

declare(strict_types=1);

namespace App\Domain\Ports;

/**
 * The previously-recorded outcome of a request made under a given
 * Idempotency-Key, replayed verbatim to the caller instead of re-executing
 * the command (contracts/openapi.v1.yaml parameters.IdempotencyKey: "Same
 * key + same principal + same semantic request must replay the original
 * result.").
 */
final class IdempotentResult
{
    /** @param array<string, mixed> $responseBody */
    public function __construct(
        public readonly int $responseStatus,
        public readonly array $responseBody,
        public readonly ?string $resourceId,
    ) {}
}

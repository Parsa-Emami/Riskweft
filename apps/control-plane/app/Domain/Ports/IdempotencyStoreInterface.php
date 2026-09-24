<?php

declare(strict_types=1);

namespace App\Domain\Ports;

interface IdempotencyStoreInterface
{
    /**
     * Atomically check-and-reserve an Idempotency-Key for one route+principal.
     *
     *  - Never seen before -> a reservation row is written and null is
     *    returned, meaning "proceed, then call complete()".
     *  - Seen before with the SAME request fingerprint and already completed
     *    -> the original IdempotentResult is returned to replay verbatim
     *    (contracts/openapi.v1.yaml: "Same key + same principal + same
     *    semantic request must replay the original result.").
     *  - Seen before but not yet completed (a concurrent duplicate is still
     *    in flight) -> throws IdempotencyKeyConflictException so the
     *    duplicate fails fast (429/409) instead of double-executing the
     *    command; the client's normal retry behaviour resolves this.
     *
     * @throws IdempotencyKeyConflictException same key+principal+route
     *         reused for a request with a different fingerprint, or a
     *         concurrent duplicate is still in flight.
     */
    public function reserveOrReplay(string $key, ?string $principalId, string $route, string $requestFingerprint): ?IdempotentResult;

    /** @param array<string, mixed> $responseBody */
    public function complete(
        string $key,
        ?string $principalId,
        string $route,
        int $responseStatus,
        array $responseBody,
        ?string $resourceId,
    ): void;
}

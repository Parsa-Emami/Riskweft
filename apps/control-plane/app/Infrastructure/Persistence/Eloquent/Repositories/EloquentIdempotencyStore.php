<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Ports\IdempotencyKeyConflictException;
use App\Domain\Ports\IdempotencyStoreInterface;
use App\Domain\Ports\IdempotentResult;
use App\Infrastructure\Persistence\Eloquent\Models\IdempotencyKeyModel;
use Illuminate\Database\QueryException;

final class EloquentIdempotencyStore implements IdempotencyStoreInterface
{
    public function reserveOrReplay(string $key, ?string $principalId, string $route, string $requestFingerprint): ?IdempotentResult
    {
        $existing = $this->findRow($key, $principalId, $route);

        if ($existing !== null) {
            if (! hash_equals($existing->request_fingerprint, $requestFingerprint)) {
                throw new IdempotencyKeyConflictException($key);
            }

            if ($existing->completed_at === null) {
                // A prior attempt with this exact key+request is still in
                // flight (or crashed before completing). Fail closed rather
                // than risk executing the command twice; the client's
                // normal retry/backoff resolves this.
                throw new IdempotencyKeyConflictException($key);
            }

            return new IdempotentResult(
                responseStatus: (int) $existing->response_status,
                responseBody: $existing->response_body ?? [],
                resourceId: $existing->resource_id,
            );
        }

        try {
            IdempotencyKeyModel::create([
                'idempotency_key' => $key,
                'principal_user_id' => $principalId,
                'route' => $route,
                'request_fingerprint' => $requestFingerprint,
                'created_at' => now(),
            ]);
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                // Lost the race to a concurrent identical request that
                // inserted its reservation row first.
                throw new IdempotencyKeyConflictException($key);
            }

            throw $e;
        }

        return null;
    }

    public function complete(
        string $key,
        ?string $principalId,
        string $route,
        int $responseStatus,
        array $responseBody,
        ?string $resourceId,
    ): void {
        IdempotencyKeyModel::where('idempotency_key', $key)
            ->where('principal_user_id', $principalId)
            ->where('route', $route)
            ->update([
                'response_status' => $responseStatus,
                'response_body' => $responseBody,
                'resource_id' => $resourceId,
                'completed_at' => now(),
            ]);
    }

    private function findRow(string $key, ?string $principalId, string $route): ?IdempotencyKeyModel
    {
        return IdempotencyKeyModel::where('idempotency_key', $key)
            ->where('principal_user_id', $principalId)
            ->where('route', $route)
            ->first();
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        // Postgres SQLSTATE 23505 = unique_violation.
        return $e->getCode() === '23505' || str_contains($e->getMessage(), '23505');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Support;

use Illuminate\Http\JsonResponse;

/**
 * Builds every response in the exact shape of
 * contracts/openapi.v1.yaml components.schemas.OperationResult / ApiError.
 */
final class ApiResponse
{
    /** @param mixed $data */
    public static function accepted(string $requestId, string $status, ?string $resourceId, ?int $version, $data): JsonResponse
    {
        return self::operationResult($requestId, $status, $resourceId, $version, $data, 202);
    }

    /** @param mixed $data */
    public static function ok(string $requestId, string $status, ?string $resourceId, ?int $version, $data): JsonResponse
    {
        return self::operationResult($requestId, $status, $resourceId, $version, $data, 200);
    }

    /** @param mixed $data */
    private static function operationResult(string $requestId, string $status, ?string $resourceId, ?int $version, $data, int $httpStatus): JsonResponse
    {
        return response()->json([
            'request_id' => $requestId,
            'operation_id' => null,
            'resource_id' => $resourceId,
            'version' => $version,
            'status' => $status,
            'data' => $data,
        ], $httpStatus);
    }

    /** @param array<string, mixed>|list<mixed>|null $details */
    public static function error(string $requestId, int $status, string $code, string $message, bool $retryable, $details = null): JsonResponse
    {
        return response()->json([
            'request_id' => $requestId,
            'code' => $code,
            'message' => $message,
            'retryable' => $retryable,
            'details' => $details,
        ], $status, ['Content-Type' => 'application/problem+json']);
    }
}

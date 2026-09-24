<?php

return [
    // Phase 0 exposes no background workers of its own; the transactional
    // outbox row is written synchronously in the same DB transaction as the
    // authoritative state change (see App\Infrastructure\Outbox). A queued
    // relay worker that drains outbox_messages to Valkey streams is an
    // explicitly deferred follow-up (see docs/phase-0-exit-evidence.md).
    'default' => env('QUEUE_CONNECTION', 'sync'),

    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('SERVICE_NAME', 'riskweft'),
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => true,
        ],
    ],

    'batching' => [
        'database' => 'pgsql',
        'table' => 'job_batches',
    ],

    'failed' => [
        'driver' => 'database-uuids',
        'database' => 'pgsql',
        'table' => 'failed_jobs',
    ],
];

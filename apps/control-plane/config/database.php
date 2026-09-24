<?php

use App\Infrastructure\Config\SecretReferenceResolver;

$pgsqlDsn = SecretReferenceResolver::resolve(env('DATABASE_DSN_REF'));
$pgsqlParts = $pgsqlDsn !== null ? SecretReferenceResolver::parseDsn($pgsqlDsn) : [];

return [
    // Single canonical relational connection. RiskWeft's authoritative store
    // is PostgreSQL only (spec/05_DATABASE_SCHEMA_BLUEPRINT.md) - there is
    // deliberately no secondary/read-replica config until that is a measured
    // requirement (see spec/08_SLO_CAPACITY_BUDGETS.md).
    'default' => 'pgsql',

    'connections' => [
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => $pgsqlParts['host'] ?? '127.0.0.1',
            'port' => $pgsqlParts['port'] ?? '5432',
            'database' => $pgsqlParts['database'] ?? 'riskweft',
            'username' => $pgsqlParts['username'] ?? 'riskweft',
            'password' => $pgsqlParts['password'] ?? '',
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('APP_ENV') === 'production' ? 'require' : 'prefer',
        ],
    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    // Valkey (Redis-protocol compatible) backs cache/queue in v1. There is no
    // separate "redis" business-config key: the connection is derived from
    // the same VALKEY_DSN_REF secret reference used everywhere else.
    'redis' => [
        'client' => 'phpredis',

        'default' => (function () {
            $dsn = SecretReferenceResolver::resolve(env('VALKEY_DSN_REF'));
            $parts = $dsn !== null ? SecretReferenceResolver::parseDsn($dsn) : [];

            return [
                'host' => $parts['host'] ?? '127.0.0.1',
                'password' => $parts['password'] ?? null,
                'port' => $parts['port'] ?? '6379',
                'database' => isset($parts['database']) && $parts['database'] !== ''
                    ? $parts['database']
                    : '0',
            ];
        })(),
    ],
];

<?php

declare(strict_types=1);

namespace App\Infrastructure\Config;

/**
 * Resolves the "secret://<provider>/<name>" reference URIs used by
 * DATABASE_DSN_REF / VALKEY_DSN_REF (spec/11_CONFIGURATION_REFERENCE.md:
 * "Secret/reference URI; never embed production credentials in config.").
 *
 * Config values themselves stay non-secret and safe to log; only the
 * *reference* is configuration. Resolving a reference to its actual value is
 * an infrastructure adapter concern, which is exactly what this class is -
 * it is intentionally called from config/*.php (framework wiring), never
 * from the Domain layer.
 *
 * v1 implements only the "local" provider (env var
 * SECRET_LOCAL_<UPPER_SNAKE_NAME>), which is what local development, tests
 * and CI use. Any other provider (vault, aws-secrets-manager, ...) is a
 * deployment/infrastructure-phase concern - see the empty infra/ and
 * security/ directories at the repository root - and fails loudly rather
 * than silently rather than guessing.
 */
final class SecretReferenceResolver
{
    private const SUPPORTED_LOCAL_PROVIDER = 'local';

    /**
     * @return string|null null only when $reference itself is null/empty
     *         (i.e. the caller did not configure this secret at all).
     */
    public static function resolve(?string $reference): ?string
    {
        if ($reference === null || trim($reference) === '') {
            return null;
        }

        if (! str_starts_with($reference, 'secret://')) {
            // Not a reference at all - treat as a literal value. This keeps a
            // throwaway local .env (e.g. a raw postgres:// URL pasted in by
            // habit) working rather than hard-failing, while every
            // documented example and the committed .env.example always use
            // a real secret:// reference.
            return $reference;
        }

        $withoutScheme = substr($reference, strlen('secret://'));
        [$provider, $name] = array_pad(explode('/', $withoutScheme, 2), 2, null);

        if ($provider !== self::SUPPORTED_LOCAL_PROVIDER || $name === null || $name === '') {
            throw new \RuntimeException(
                "Unsupported secret reference provider [{$provider}] in [{$reference}]. ".
                "Only the '".self::SUPPORTED_LOCAL_PROVIDER."' provider is implemented ".
                'in this phase; a production secret provider adapter is an '.
                'infrastructure-phase concern.'
            );
        }

        $envName = self::localEnvVarName($name);
        $value = env($envName);

        if ($value === null || $value === '') {
            throw new \RuntimeException(
                "Missing local secret value for reference [{$reference}]: expected environment ".
                "variable [{$envName}] to be set (see apps/control-plane/.env.example)."
            );
        }

        return $value;
    }

    public static function localEnvVarName(string $secretName): string
    {
        return 'SECRET_LOCAL_'.strtoupper((string) preg_replace('/[^a-zA-Z0-9]+/', '_', $secretName));
    }

    /**
     * Parses a "scheme://[user[:pass]@]host[:port][/database]" DSN into its
     * parts. Deliberately tolerant of a missing scheme/credentials so it
     * works for both the pgsql:// and redis:// shapes used in this project.
     *
     * @return array{host: string, port: string|null, database: string, username: string|null, password: string|null}
     */
    public static function parseDsn(string $dsn): array
    {
        $parts = parse_url($dsn);

        if ($parts === false || ! isset($parts['host'])) {
            throw new \RuntimeException('Unable to parse DSN: malformed connection string.');
        }

        return [
            'host' => $parts['host'],
            'port' => isset($parts['port']) ? (string) $parts['port'] : null,
            'database' => isset($parts['path']) ? ltrim($parts['path'], '/') : '',
            'username' => isset($parts['user']) ? rawurldecode($parts['user']) : null,
            'password' => isset($parts['pass']) ? rawurldecode($parts['pass']) : null,
        ];
    }
}

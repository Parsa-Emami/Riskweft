<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use App\Infrastructure\Config\SecretReferenceResolver;
use PHPUnit\Framework\TestCase;

final class SecretReferenceResolverTest extends TestCase
{
    public function test_lookup_is_injected_and_receives_only_the_reference_name(): void
    {
        $names = [];
        $value = SecretReferenceResolver::resolve('secret://local/postgres-dsn', static function (string $name) use (&$names): string {
            $names[] = $name;

            return 'synthetic-dsn';
        });

        self::assertSame('synthetic-dsn', $value);
        self::assertSame(['SECRET_LOCAL_POSTGRES_DSN'], $names);
    }

    public function test_null_and_literal_inputs_do_not_read_environment(): void
    {
        $unexpected = static fn (): never => throw new \LogicException('Lookup must not run.');

        self::assertNull(SecretReferenceResolver::resolve(null, $unexpected));
        self::assertSame('literal-dsn', SecretReferenceResolver::resolve('literal-dsn', $unexpected));
    }

    public function test_non_string_secret_values_fail_closed(): void
    {
        $this->expectException(\RuntimeException::class);
        SecretReferenceResolver::resolve('secret://local/postgres-dsn', static fn (): bool => false);
    }
}

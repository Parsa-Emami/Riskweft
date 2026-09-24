<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Architecture;

use App\Domain\Architecture\ContentHash;
use PHPUnit\Framework\TestCase;

final class ContentHashTest extends TestCase
{
    public function test_is_self_describing_and_round_trips_through_string_form(): void
    {
        $hash = ContentHash::compute('{"a":1}', 'sha256');

        self::assertSame('sha256', $hash->algorithm());
        self::assertSame(hash('sha256', '{"a":1}'), $hash->digestHex());
        self::assertSame('sha256:'.hash('sha256', '{"a":1}'), $hash->toString());

        $roundTripped = ContentHash::fromString($hash->toString());
        self::assertTrue($hash->equals($roundTripped));
    }

    public function test_rejects_an_unsupported_algorithm(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ContentHash::compute('payload', 'not-a-real-algorithm');
    }

    public function test_rejects_a_malformed_string_form(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ContentHash::fromString('not-a-valid-hash-string');
    }

    public function test_different_algorithms_are_never_equal_even_with_the_same_digest_text(): void
    {
        $a = ContentHash::fromString('sha256:deadbeef');
        $b = ContentHash::fromString('sha512:deadbeef');

        self::assertFalse($a->equals($b));
    }
}

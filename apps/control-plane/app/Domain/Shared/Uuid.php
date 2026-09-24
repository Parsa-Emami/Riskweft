<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/**
 * Minimal, dependency-free UUIDv4 value object.
 *
 * The Domain layer may not depend on Laravel or a third-party UUID package
 * (spec/01_DOMAIN_CATALOG.md), so identifiers are generated with PHP's own
 * random_bytes() rather than Illuminate\Support\Str::uuid() or ramsey/uuid.
 * Infrastructure adapters are free to use richer libraries internally as
 * long as they hand the Domain layer a value that satisfies this type.
 */
final class Uuid
{
    private const PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    private function __construct(private readonly string $value) {}

    public static function generate(): self
    {
        $bytes = random_bytes(16);

        // Set version (4) and variant (RFC 4122) bits.
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        $hex = bin2hex($bytes);

        $formatted = sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );

        return new self($formatted);
    }

    public static function fromString(string $value): self
    {
        $trimmed = strtolower(trim($value));

        if (preg_match(self::PATTERN, $trimmed) !== 1) {
            throw new \InvalidArgumentException("Not a valid UUID: [{$value}]");
        }

        return new self($trimmed);
    }

    public static function isValid(string $value): bool
    {
        return preg_match(self::PATTERN, strtolower(trim($value))) === 1;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

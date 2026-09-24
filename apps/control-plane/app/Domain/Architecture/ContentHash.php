<?php

declare(strict_types=1);

namespace App\Domain\Architecture;

/**
 * A self-describing content hash: "<algorithm>:<lowercase-hex-digest>".
 *
 * Self-describing so that CANONICAL_HASH_ALGORITHM can change between
 * deployments/over time (spec/12_MIGRATION_UPGRADE_ROLLBACK.md) without ever
 * making an old stored hash ambiguous about which algorithm produced it.
 */
final class ContentHash
{
    private function __construct(
        private readonly string $algorithm,
        private readonly string $digestHex,
    ) {}

    public static function compute(string $canonicalJson, string $algorithm): self
    {
        $normalizedAlgorithm = strtolower(trim($algorithm));

        if ($normalizedAlgorithm === '' || ! in_array($normalizedAlgorithm, hash_algos(), true)) {
            throw new \InvalidArgumentException("Unsupported canonical hash algorithm: [{$algorithm}].");
        }

        return new self($normalizedAlgorithm, hash($normalizedAlgorithm, $canonicalJson));
    }

    public static function fromString(string $value): self
    {
        $parts = explode(':', $value, 2);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new \InvalidArgumentException("Malformed content hash: [{$value}]. Expected \"<algorithm>:<hex-digest>\".");
        }

        return new self($parts[0], strtolower($parts[1]));
    }

    public function algorithm(): string
    {
        return $this->algorithm;
    }

    public function digestHex(): string
    {
        return $this->digestHex;
    }

    public function toString(): string
    {
        return "{$this->algorithm}:{$this->digestHex}";
    }

    public function equals(self $other): bool
    {
        // hash_equals for defense-in-depth even though this is not a secret
        // comparison; it costs nothing and removes any timing-channel
        // discussion from future review.
        return $this->algorithm === $other->algorithm
            && hash_equals($this->digestHex, $other->digestHex);
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}

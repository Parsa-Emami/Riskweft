<?php

declare(strict_types=1);

namespace App\Domain\Architecture;

/**
 * Turns an ArchitectureDocument into the single canonical UTF-8 JSON string
 * that ContentHash is computed from.
 *
 * Determinism rules (this is the Phase 0 "canonicalization golden test" /
 * "hash stability test" contract - tests/Unit/Domain/Architecture/
 * CanonicalizerTest.php, plus scripts/verify-canonicalization.py which
 * mirrors this exact algorithm in an executable, framework-free form):
 *
 *   1. Entities/relationships are sorted by their stable id
 *      (ArchitectureDocument::toCanonicalArray) - submission order never
 *      affects the result.
 *   2. Object keys are sorted recursively, byte-wise (SORT_STRING) - key
 *      order in the client payload never affects the result.
 *   3. Encoding is fixed: compact (no pretty-print/whitespace), unescaped
 *      slashes and unicode, UTF-8. The same logical value always produces
 *      the same bytes.
 *   4. Floating point numbers are rejected outright rather than
 *      canonicalized, because IEEE-754 formatting is not guaranteed
 *      byte-stable across PHP builds/locales; integers, strings, bools,
 *      null and nested arrays/maps are unambiguous and are all that a
 *      security-relevant architecture document needs.
 */
final class Canonicalizer
{
    public static function canonicalize(ArchitectureDocument $document): string
    {
        return self::encode($document->toCanonicalArray());
    }

    /**
     * Exposed for property-based fixtures that build a raw canonical array
     * directly (e.g. golden tests) without an ArchitectureDocument.
     */
    public static function encode(mixed $value): string
    {
        $normalized = self::normalize($value);

        $json = json_encode(
            $normalized,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        return $json;
    }

    private static function normalize(mixed $value): mixed
    {
        if (is_float($value)) {
            throw new \InvalidArgumentException(
                'Floating point values are not permitted in a canonical architecture document.'
            );
        }

        if (is_object($value)) {
            throw new \InvalidArgumentException(
                'Objects/resources are not permitted in a canonical architecture document; use arrays.'
            );
        }

        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(self::normalize(...), $value);
        }

        ksort($value, SORT_STRING);

        return array_map(self::normalize(...), $value);
    }
}

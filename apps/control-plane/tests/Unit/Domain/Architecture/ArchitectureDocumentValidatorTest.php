<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Architecture;

use App\Domain\Architecture\ArchitectureDocumentValidator;
use App\Domain\Architecture\Exceptions\InvalidArchitectureDocumentException;
use PHPUnit\Framework\TestCase;

/**
 * spec/09_SECURITY_ABUSE_TESTS.md and implementation-start/failure-catalog.json
 * both name these as required negative cases; this suite is the Domain-layer
 * half of that proof (tests/Feature/Api/V1/CommitArchitectureRevisionTest.php
 * covers the same cases through the HTTP boundary end to end).
 */
final class ArchitectureDocumentValidatorTest extends TestCase
{
    private const ID_A = '11111111-1111-4111-8111-111111111111';

    private const ID_B = '22222222-2222-4222-8222-222222222222';

    public function test_accepts_a_minimal_valid_document(): void
    {
        $document = ArchitectureDocumentValidator::validate([
            'metadata' => ['name' => 'Minimal'],
            'entities' => [],
            'relationships' => [],
        ], 2_097_152);

        self::assertSame('Minimal', $document->name);
        self::assertSame(0, $document->entityCount());
    }

    public function test_rejects_a_relationship_with_a_dangling_source_reference(): void
    {
        $this->expectException(InvalidArchitectureDocumentException::class);

        try {
            ArchitectureDocumentValidator::validate([
                'metadata' => ['name' => 'Dangling'],
                'entities' => [
                    ['id' => self::ID_A, 'kind' => 'component', 'name' => 'API'],
                ],
                'relationships' => [
                    ['id' => '33333333-3333-4333-8333-333333333333', 'kind' => 'data_flow', 'source_entity_id' => self::ID_B, 'target_entity_id' => self::ID_A],
                ],
            ], 2_097_152);
        } catch (InvalidArchitectureDocumentException $e) {
            self::assertNotEmpty(array_filter($e->violations(), static fn (string $v) => str_contains($v, 'dangling reference')));

            throw $e;
        }
    }

    public function test_rejects_a_duplicate_entity_id_within_the_same_document(): void
    {
        $this->expectException(InvalidArchitectureDocumentException::class);

        ArchitectureDocumentValidator::validate([
            'metadata' => ['name' => 'Duplicate'],
            'entities' => [
                ['id' => self::ID_A, 'kind' => 'component', 'name' => 'API'],
                ['id' => self::ID_A, 'kind' => 'component', 'name' => 'API again'],
            ],
            'relationships' => [],
        ], 2_097_152);
    }

    public function test_rejects_an_unknown_entity_kind(): void
    {
        $this->expectException(InvalidArchitectureDocumentException::class);

        ArchitectureDocumentValidator::validate([
            'metadata' => ['name' => 'BadKind'],
            'entities' => [
                ['id' => self::ID_A, 'kind' => 'spaceship', 'name' => 'API'],
            ],
            'relationships' => [],
        ], 2_097_152);
    }

    public function test_rejects_a_reserved_canvas_layout_attribute_key(): void
    {
        // Frozen v1 Invariant #2: canvas coordinates cannot enter the
        // canonical document, not even inside a generic attributes bag.
        $this->expectException(InvalidArchitectureDocumentException::class);

        ArchitectureDocumentValidator::validate([
            'metadata' => ['name' => 'LayoutLeak'],
            'entities' => [
                ['id' => self::ID_A, 'kind' => 'component', 'name' => 'API', 'attributes' => ['x' => 120, 'y' => 40]],
            ],
            'relationships' => [],
        ], 2_097_152);
    }

    public function test_rejects_a_document_larger_than_the_configured_byte_limit(): void
    {
        $this->expectException(InvalidArchitectureDocumentException::class);

        ArchitectureDocumentValidator::validate([
            'metadata' => ['name' => 'Oversized'],
            'entities' => [
                ['id' => self::ID_A, 'kind' => 'component', 'name' => 'API', 'attributes' => ['blob' => str_repeat('x', 1000)]],
            ],
            'relationships' => [],
        ], 100);
    }

    public function test_rejects_a_self_referencing_relationship(): void
    {
        $this->expectException(InvalidArchitectureDocumentException::class);

        ArchitectureDocumentValidator::validate([
            'metadata' => ['name' => 'SelfLoop'],
            'entities' => [
                ['id' => self::ID_A, 'kind' => 'component', 'name' => 'API'],
            ],
            'relationships' => [
                ['id' => '33333333-3333-4333-8333-333333333333', 'kind' => 'data_flow', 'source_entity_id' => self::ID_A, 'target_entity_id' => self::ID_A],
            ],
        ], 2_097_152);
    }

    public function test_collects_every_violation_instead_of_failing_on_the_first(): void
    {
        try {
            ArchitectureDocumentValidator::validate([
                'metadata' => ['name' => ''],
                'entities' => [
                    ['id' => 'not-a-uuid', 'kind' => 'component', 'name' => 'API'],
                ],
                'relationships' => [],
            ], 2_097_152);
            self::fail('Expected InvalidArchitectureDocumentException.');
        } catch (InvalidArchitectureDocumentException $e) {
            self::assertGreaterThanOrEqual(2, count($e->violations()));
        }
    }
}

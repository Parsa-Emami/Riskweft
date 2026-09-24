<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Architecture;

use App\Domain\Architecture\ArchitectureDocument;
use App\Domain\Architecture\ArchitectureEntity;
use App\Domain\Architecture\ArchitectureRelationship;
use App\Domain\Architecture\Canonicalizer;
use App\Domain\Architecture\ContentHash;
use App\Domain\Architecture\EntityKind;
use App\Domain\Architecture\RelationshipKind;
use PHPUnit\Framework\TestCase;

/**
 * Phase 0 Definition of Done: "The same logical architecture normalizes to
 * the same canonical document and revision hash across repeated runs"
 * (ROADMAP_V7.md). This is a pure-PHP unit test with zero Laravel bootstrap
 * on purpose - the Domain layer does not depend on the framework, so
 * neither does the test that pins its most important property.
 *
 * A framework-free, independently executable mirror of this exact algorithm
 * also lives at scripts/verify-canonicalization.py, run and checked in
 * during this phase's exit evidence (docs/phase-0-exit-evidence.md) as
 * additional, actually-executed proof of the determinism property, since
 * this repository's sandbox could not run PHPUnit itself at authoring time.
 */
final class CanonicalizerTest extends TestCase
{
    private const FIXED_ID_A = '11111111-1111-4111-8111-111111111111';

    private const FIXED_ID_B = '22222222-2222-4222-8222-222222222222';

    private const FIXED_REL_ID = '33333333-3333-4333-8333-333333333333';

    public function test_identical_document_canonicalizes_identically_across_repeated_runs(): void
    {
        $document = $this->sampleDocument();

        $first = Canonicalizer::canonicalize($document);
        $second = Canonicalizer::canonicalize($this->sampleDocument());

        self::assertSame($first, $second);

        $hash1 = ContentHash::compute($first, 'sha256');
        $hash2 = ContentHash::compute($second, 'sha256');
        self::assertTrue($hash1->equals($hash2));
    }

    public function test_entity_and_relationship_submission_order_does_not_affect_the_canonical_form(): void
    {
        $entityA = new ArchitectureEntity(self::FIXED_ID_A, EntityKind::Component, 'API', null, []);
        $entityB = new ArchitectureEntity(self::FIXED_ID_B, EntityKind::Asset, 'Customer DB', null, []);
        $relationship = new ArchitectureRelationship(
            self::FIXED_REL_ID,
            RelationshipKind::DataFlow,
            self::FIXED_ID_A,
            self::FIXED_ID_B,
            null,
            [],
        );

        $inOrder = new ArchitectureDocument('Sample', null, [$entityA, $entityB], [$relationship]);
        $reversed = new ArchitectureDocument('Sample', null, [$entityB, $entityA], [$relationship]);

        self::assertSame(Canonicalizer::canonicalize($inOrder), Canonicalizer::canonicalize($reversed));
    }

    public function test_attribute_key_insertion_order_does_not_affect_the_canonical_form(): void
    {
        $entity1 = new ArchitectureEntity(self::FIXED_ID_A, EntityKind::Component, 'API', null, ['a' => 1, 'b' => 2, 'z' => 3]);
        $entity2 = new ArchitectureEntity(self::FIXED_ID_A, EntityKind::Component, 'API', null, ['z' => 3, 'a' => 1, 'b' => 2]);

        $doc1 = new ArchitectureDocument('Sample', null, [$entity1], []);
        $doc2 = new ArchitectureDocument('Sample', null, [$entity2], []);

        self::assertSame(Canonicalizer::canonicalize($doc1), Canonicalizer::canonicalize($doc2));
    }

    public function test_nested_map_keys_are_sorted_recursively(): void
    {
        $entity1 = new ArchitectureEntity(self::FIXED_ID_A, EntityKind::Asset, 'DB', null, [
            'classification' => ['level' => 'confidential', 'category' => 'pii'],
        ]);
        $entity2 = new ArchitectureEntity(self::FIXED_ID_A, EntityKind::Asset, 'DB', null, [
            'classification' => ['category' => 'pii', 'level' => 'confidential'],
        ]);

        $doc1 = new ArchitectureDocument('Sample', null, [$entity1], []);
        $doc2 = new ArchitectureDocument('Sample', null, [$entity2], []);

        self::assertSame(Canonicalizer::canonicalize($doc1), Canonicalizer::canonicalize($doc2));
    }

    public function test_a_semantically_different_document_produces_a_different_hash(): void
    {
        $base = $this->sampleDocument();
        $changed = new ArchitectureDocument(
            'Sample Architecture',
            null,
            [new ArchitectureEntity(self::FIXED_ID_A, EntityKind::Component, 'API (renamed)', null, [])],
            [],
        );

        $baseHash = ContentHash::compute(Canonicalizer::canonicalize($base), 'sha256');
        $changedHash = ContentHash::compute(Canonicalizer::canonicalize($changed), 'sha256');

        self::assertFalse($baseHash->equals($changedHash));
    }

    public function test_list_element_order_is_preserved_where_it_is_semantically_a_list(): void
    {
        // Determinism means "same logical content -> same bytes", not "all
        // arrays get reordered" - a list-typed attribute value is genuinely
        // ordered data (e.g. an ordered tag list) and must round-trip as-is.
        $entity1 = new ArchitectureEntity(self::FIXED_ID_A, EntityKind::Component, 'API', null, ['tags' => ['first', 'second']]);
        $entity2 = new ArchitectureEntity(self::FIXED_ID_A, EntityKind::Component, 'API', null, ['tags' => ['second', 'first']]);

        $doc1 = new ArchitectureDocument('Sample', null, [$entity1], []);
        $doc2 = new ArchitectureDocument('Sample', null, [$entity2], []);

        self::assertNotSame(Canonicalizer::canonicalize($doc1), Canonicalizer::canonicalize($doc2));
    }

    public function test_floating_point_attribute_values_are_rejected(): void
    {
        $entity = new ArchitectureEntity(self::FIXED_ID_A, EntityKind::Component, 'API', null, ['score' => 0.5]);
        $document = new ArchitectureDocument('Sample', null, [$entity], []);

        $this->expectException(\InvalidArgumentException::class);
        Canonicalizer::canonicalize($document);
    }

    /**
     * Golden test: pins the *exact* byte output for one fixed input. If
     * this ever fails after a supposedly-unrelated change, the canonical
     * wire format changed - which requires the ADR/migration process in
     * spec/12_MIGRATION_UPGRADE_ROLLBACK.md, not a quiet test update.
     */
    public function test_golden_canonical_json_for_a_fixed_document(): void
    {
        $document = new ArchitectureDocument(
            'Golden',
            null,
            [new ArchitectureEntity(self::FIXED_ID_A, EntityKind::Component, 'API', null, ['b' => 2, 'a' => 1])],
            [],
        );

        $expected = '{"entities":[{"attributes":{"a":1,"b":2},"description":null,"id":"'.self::FIXED_ID_A.
            '","kind":"component","name":"API"}],"metadata":{"description":null,"name":"Golden"},'.
            '"relationships":[],"schema_version":1}';

        self::assertSame($expected, Canonicalizer::canonicalize($document));
        self::assertSame(
            'sha256:'.hash('sha256', $expected),
            ContentHash::compute(Canonicalizer::canonicalize($document), 'sha256')->toString(),
        );
    }

    private function sampleDocument(): ArchitectureDocument
    {
        return new ArchitectureDocument(
            'Sample Architecture',
            'A small reference architecture used across Phase 0 tests.',
            [
                new ArchitectureEntity(self::FIXED_ID_A, EntityKind::Component, 'API', null, ['exposure' => 'internal']),
                new ArchitectureEntity(self::FIXED_ID_B, EntityKind::Asset, 'Customer DB', null, ['classification' => 'confidential']),
            ],
            [
                new ArchitectureRelationship(
                    self::FIXED_REL_ID,
                    RelationshipKind::DataFlow,
                    self::FIXED_ID_A,
                    self::FIXED_ID_B,
                    'reads customer records',
                    ['protocol' => 'tls'],
                ),
            ],
        );
    }
}

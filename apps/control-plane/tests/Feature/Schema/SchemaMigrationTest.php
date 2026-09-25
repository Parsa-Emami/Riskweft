<?php

declare(strict_types=1);

namespace Tests\Feature\Schema;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Phase 0 "Required verification: schema migration tests" and Definition of
 * Done "CI is green from a clean checkout". RefreshDatabase already runs
 * every migration fresh before this class' tests; test_migrate_fresh_
 * runs_cleanly_from_an_empty_database below additionally proves the exact
 * command a clean checkout runs (`php artisan migrate:fresh`) succeeds on
 * its own, independent of the test framework's own bootstrapping.
 */
final class SchemaMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrate_fresh_runs_cleanly_from_an_empty_database(): void
    {
        $exitCode = Artisan::call('migrate:fresh', ['--force' => true]);

        self::assertSame(0, $exitCode, Artisan::output());
    }

    /** @return iterable<string, array{0: string, 1: list<string>}> */
    public static function tableColumnProvider(): iterable
    {
        yield 'projects' => ['projects', ['id', 'name', 'slug', 'head_revision_id', 'head_version']];
        yield 'architecture_revisions' => ['architecture_revisions', [
            'id', 'project_id', 'parent_revision_id', 'sequence_no', 'status',
            'canonical_document', 'content_hash', 'hash_algorithm',
            'entity_count', 'relationship_count', 'committed_by_user_id', 'committed_at',
        ]];
        yield 'revision_entities' => ['revision_entities', ['id', 'revision_id', 'entity_id', 'kind', 'name', 'description', 'attributes']];
        yield 'revision_relationships' => ['revision_relationships', [
            'id', 'revision_id', 'relationship_id', 'kind', 'source_entity_row_id', 'target_entity_row_id', 'label', 'attributes',
        ]];
        yield 'project_members' => ['project_members', ['id', 'project_id', 'user_id', 'role']];
        yield 'outbox_messages' => ['outbox_messages', ['id', 'aggregate_type', 'aggregate_id', 'event_type', 'event_id', 'payload', 'correlation_id', 'occurred_at', 'published_at']];
        yield 'audit_events' => ['audit_events', ['id', 'actor_user_id', 'action', 'resource_type', 'resource_id', 'project_id', 'correlation_id', 'metadata', 'occurred_at']];
        yield 'idempotency_keys' => ['idempotency_keys', ['id', 'idempotency_key', 'principal_user_id', 'route', 'request_fingerprint', 'response_status', 'response_body', 'resource_id']];
    }

    /**
     * @param  list<string>  $expectedColumns
     */
    #[DataProvider('tableColumnProvider')]
    public function test_authoritative_tables_and_columns_exist(string $table, array $expectedColumns): void
    {
        self::assertTrue(Schema::hasTable($table), "expected table [{$table}] to exist");
        self::assertTrue(Schema::hasColumns($table, $expectedColumns), "expected [{$table}] to have columns: ".implode(', ', $expectedColumns));
    }

    public function test_architecture_revisions_has_no_updated_at_column(): void
    {
        // Structural proof of Frozen v1 Invariant #1: there is no column an
        // UPDATE could even plausibly mean "this changed after commit".
        self::assertFalse(Schema::hasColumn('architecture_revisions', 'updated_at'));
    }

    public function test_sequence_number_is_unique_per_project(): void
    {
        $indexes = Schema::getIndexes('architecture_revisions');
        $hasUniqueProjectSequence = collect($indexes)->contains(
            fn (array $index) => $index['unique'] && $index['columns'] === ['project_id', 'sequence_no']
        );

        self::assertTrue($hasUniqueProjectSequence, 'expected a unique (project_id, sequence_no) index on architecture_revisions');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Schema;

use App\Infrastructure\Persistence\Eloquent\Models\ArchitectureRevisionModel;
use App\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Defense-in-depth proof for Frozen v1 Invariant #1: even a raw UPDATE
 * issued directly against the database (bypassing the application layer
 * entirely) is rejected by the trigger installed in
 * database/migrations/..._000012_add_immutability_guard_to_architecture_revisions.php.
 */
final class ArchitectureRevisionImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_the_canonical_document_of_a_committed_revision_is_rejected_by_the_database(): void
    {
        $project = ProjectModel::factory()->create();
        $revision = ArchitectureRevisionModel::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'project_id' => $project->id,
            'parent_revision_id' => null,
            'sequence_no' => 1,
            'status' => \App\Domain\Architecture\RevisionStatus::Draft,
            'canonical_document' => ['schema_version' => 1, 'metadata' => ['name' => 'x', 'description' => null], 'entities' => [], 'relationships' => []],
            'content_hash' => 'sha256:'.hash('sha256', 'x'),
            'hash_algorithm' => 'sha256',
            'entity_count' => 0,
            'relationship_count' => 0,
            'committed_by_user_id' => null,
            'committed_at' => now(),
        ]);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessageMatches('/immutable once committed/');

        DB::table('architecture_revisions')
            ->where('id', $revision->id)
            ->update(['content_hash' => 'sha256:'.hash('sha256', 'tampered')]);
    }

    public function test_the_status_column_alone_remains_updatable(): void
    {
        // The trigger only guards content-bearing columns; the future
        // review-workflow state machine (STATE_MACHINES_V7.md) needs
        // `status` itself to stay mutable.
        $project = ProjectModel::factory()->create();
        $revision = ArchitectureRevisionModel::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'project_id' => $project->id,
            'parent_revision_id' => null,
            'sequence_no' => 1,
            'status' => \App\Domain\Architecture\RevisionStatus::Draft,
            'canonical_document' => ['schema_version' => 1, 'metadata' => ['name' => 'x', 'description' => null], 'entities' => [], 'relationships' => []],
            'content_hash' => 'sha256:'.hash('sha256', 'x'),
            'hash_algorithm' => 'sha256',
            'entity_count' => 0,
            'relationship_count' => 0,
            'committed_by_user_id' => null,
            'committed_at' => now(),
        ]);

        DB::table('architecture_revisions')->where('id', $revision->id)->update(['status' => 'REVIEW']);

        self::assertSame('REVIEW', DB::table('architecture_revisions')->where('id', $revision->id)->value('status'));
    }
}

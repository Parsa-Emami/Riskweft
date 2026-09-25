<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * spec/05_DATABASE_SCHEMA_BLUEPRINT.md `architecture_revisions`: the
 * canonical, immutable source of truth (Final Engine Decision #1).
 *
 * Deliberately has NO updated_at column: Frozen v1 Invariant #1
 * ("ArchitectureRevision is immutable once committed") is made structurally
 * visible, not just documented - there is no column a later UPDATE could
 * even plausibly touch to mean "this changed after commit". A trigger
 * added in a later migration
 * (..._000010_add_immutability_guard_to_architecture_revisions.php) rejects
 * any UPDATE to this table's content-bearing columns as defense in depth.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('architecture_revisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->restrictOnDelete();
            $table->uuid('parent_revision_id')->nullable();
            $table->unsignedBigInteger('sequence_no');
            $table->string('status', 32)->default('DRAFT');
            $table->jsonb('canonical_document');
            $table->string('content_hash');
            $table->string('hash_algorithm', 32);
            $table->unsignedInteger('entity_count')->default(0);
            $table->unsignedInteger('relationship_count')->default(0);
            $table->foreignUuid('committed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('committed_at');

            $table->unique(['project_id', 'sequence_no']);
            $table->index('content_hash');
            $table->index('status');
        });

        // Self-referencing foreign key added in a *separate* Schema::table()
        // call, never inline in the Schema::create() above. On PostgreSQL,
        // adding "references this same table" as part of the same
        // create-table blueprint fails - confirmed by this exact migration
        // failing with "there is no unique constraint matching given keys
        // for referenced table" on its first real run (see
        // docs/phase-0-exit-evidence.md "CI feedback - round 3"): the
        // self-referencing ALTER TABLE ADD CONSTRAINT was compiled to run
        // before Postgres considered the table's own primary key
        // established. Once the table (and its primary key) fully exists
        // as its own statement, adding the FK against it works exactly
        // like the projects.head_revision_id -> architecture_revisions.id
        // split in ..._000007_add_head_revision_foreign_key_to_projects_table.php.
        Schema::table('architecture_revisions', function (Blueprint $table) {
            $table->foreign('parent_revision_id')->references('id')->on('architecture_revisions')->nullOnDelete();
        });

        // Laravel's fluent Schema Builder has no Blueprint::check() method
        // (confirmed by this exact migration failing with
        // BadMethodCallException on its first real run - see
        // docs/phase-0-exit-evidence.md "CI feedback - round 2"); CHECK
        // constraints are added via raw SQL after the table exists.
        DB::statement("alter table architecture_revisions add constraint architecture_revisions_status_check check (status in ('DRAFT','REVIEW','APPROVED','SUPERSEDED'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('architecture_revisions');
    }
};

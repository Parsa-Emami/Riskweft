<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->foreign('parent_revision_id')->references('id')->on('architecture_revisions')->nullOnDelete();
            $table->index('content_hash');
            $table->index('status');
            $table->check("status in ('DRAFT','REVIEW','APPROVED','SUPERSEDED')");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('architecture_revisions');
    }
};

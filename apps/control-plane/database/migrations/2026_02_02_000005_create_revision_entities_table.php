<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * spec/05_DATABASE_SCHEMA_BLUEPRINT.md `revision_entities`: a typed,
 * queryable decomposition of one revision's canonical_document.entities,
 * written transactionally alongside it (Final Engine Decision #4). `id` is
 * this row's own identity; `entity_id` is the client-supplied *stable
 * logical* id that persists across revisions of the same project (needed
 * for Phase 4 semantic diff/merge).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revision_entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('revision_id')->constrained('architecture_revisions')->cascadeOnDelete();
            $table->uuid('entity_id');
            $table->string('kind', 32);
            $table->string('name');
            $table->text('description')->nullable();
            $table->jsonb('attributes')->default('{}');

            $table->unique(['revision_id', 'entity_id']);
            $table->index(['revision_id', 'kind']);
        });

        // See 2026_02_02_000004_create_architecture_revisions_table.php's
        // comment: Blueprint has no fluent check() method.
        DB::statement("alter table revision_entities add constraint revision_entities_kind_check check (kind in ('component','trust_boundary','asset'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('revision_entities');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/05_DATABASE_SCHEMA_BLUEPRINT.md `revision_relationships`. Endpoints
 * reference revision_entities.id (this revision's own rows), which gives
 * "no dangling reference" (implementation-start/failure-catalog.json) a
 * real database foreign key in addition to
 * App\Domain\Architecture\ArchitectureDocumentValidator's application-level
 * check.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revision_relationships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('revision_id')->constrained('architecture_revisions')->cascadeOnDelete();
            $table->uuid('relationship_id');
            $table->string('kind', 32);
            $table->foreignUuid('source_entity_row_id')->constrained('revision_entities')->cascadeOnDelete();
            $table->foreignUuid('target_entity_row_id')->constrained('revision_entities')->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->jsonb('attributes')->default('{}');

            $table->unique(['revision_id', 'relationship_id']);
            $table->index(['revision_id', 'kind']);
            $table->check("kind in ('data_flow','trust_boundary_membership')");
            $table->check('source_entity_row_id <> target_entity_row_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revision_relationships');
    }
};

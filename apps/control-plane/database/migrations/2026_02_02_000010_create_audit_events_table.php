<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/05_DATABASE_SCHEMA_BLUEPRINT.md `audit_events`. Deliberately
 * separate from operational logs (spec/07_OBSERVABILITY_CATALOG.md
 * "Audit != logs"): a queryable domain record with its own retention rule,
 * never rewritten by a later deletion (spec/06_DATA_CLASSIFICATION_RETENTION.md
 * "Deletion... does not rewrite immutable audit/custody history" - so this
 * table has no updated_at and no application code path that updates a row
 * after insert).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 128);
            $table->string('resource_type', 64);
            $table->uuid('resource_id');
            $table->uuid('project_id')->nullable();
            $table->uuid('correlation_id');
            $table->jsonb('metadata')->default('{}');
            $table->timestampTz('occurred_at');

            $table->index(['resource_type', 'resource_id']);
            $table->index('project_id');
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};

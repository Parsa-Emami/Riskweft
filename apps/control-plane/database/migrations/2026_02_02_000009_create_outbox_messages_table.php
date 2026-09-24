<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * spec/05_DATABASE_SCHEMA_BLUEPRINT.md `outbox_messages` /
 * contracts/event-types.v1.json envelope. `event_id` is unique so a
 * consumer's dedup-by-event-id requirement
 * (spec/03_EVENT_CATALOG.md "deduplication: required by event id") has a
 * database backstop, not just a convention. `published_at` stays null until
 * a relay worker (deferred - see docs/phase-0-exit-evidence.md) delivers it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('aggregate_type', 64);
            $table->uuid('aggregate_id');
            $table->string('event_type', 128);
            $table->uuid('event_id')->unique();
            $table->jsonb('payload');
            $table->uuid('correlation_id');
            $table->uuid('causation_id')->nullable();
            $table->timestampTz('occurred_at');
            $table->timestampTz('published_at')->nullable();

            $table->index(['aggregate_type', 'aggregate_id']);
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_messages');
    }
};

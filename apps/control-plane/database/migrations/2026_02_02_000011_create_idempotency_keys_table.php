<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * contracts/openapi.v1.yaml parameters.IdempotencyKey: "Same key + same
 * principal + same semantic request must replay the original result."
 * The unique index is what makes App\Infrastructure\Persistence\Eloquent\
 * Repositories\EloquentIdempotencyStore's reserve-then-insert race-safe
 * under concurrent duplicate requests.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('idempotency_key', 128);
            $table->uuid('principal_user_id')->nullable();
            $table->string('route', 128);
            $table->string('request_fingerprint', 64);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->jsonb('response_body')->nullable();
            $table->uuid('resource_id')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('completed_at')->nullable();

            $table->unique(['idempotency_key', 'principal_user_id', 'route']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};

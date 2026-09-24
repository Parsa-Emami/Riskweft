<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** spec/04_AUTHORIZATION_MATRIX.md roles, scoped per project. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 32);
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['project_id', 'user_id']);
            $table->check("role in ('viewer','modeler','reviewer','approver','workspace_admin')");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};

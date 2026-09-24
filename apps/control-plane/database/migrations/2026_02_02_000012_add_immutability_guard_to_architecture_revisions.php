<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Defense-in-depth for Frozen v1 Invariant #1 ("ArchitectureRevision is
 * immutable once committed"): even if application code ever grew a bug that
 * tried to UPDATE a committed revision's content, the database itself
 * refuses. The application layer never exposes an update path for these
 * columns today (there is no repository method that does it), so this
 * trigger should never fire in normal operation - that is exactly what
 * tests/Feature/Schema/ArchitectureRevisionImmutabilityTest.php proves by
 * deliberately attempting a raw UPDATE and asserting it is rejected.
 *
 * `status` is intentionally left mutable at the database level for the
 * review-workflow state machine (STATE_MACHINES_V7.md) that a later phase
 * introduces, so this trigger does not need to change shape when that
 * lands.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            create or replace function riskweft_guard_architecture_revision_immutability()
            returns trigger as $$
            begin
                if new.id is distinct from old.id
                    or new.project_id is distinct from old.project_id
                    or new.parent_revision_id is distinct from old.parent_revision_id
                    or new.sequence_no is distinct from old.sequence_no
                    or new.canonical_document is distinct from old.canonical_document
                    or new.content_hash is distinct from old.content_hash
                    or new.hash_algorithm is distinct from old.hash_algorithm
                    or new.entity_count is distinct from old.entity_count
                    or new.relationship_count is distinct from old.relationship_count
                    or new.committed_by_user_id is distinct from old.committed_by_user_id
                    or new.committed_at is distinct from old.committed_at
                then
                    raise exception 'architecture_revisions is immutable once committed (Frozen v1 Invariant #1): only "status" may change.';
                end if;
                return new;
            end;
            $$ language plpgsql;
        SQL);

        DB::statement(<<<'SQL'
            create trigger riskweft_architecture_revisions_immutability
                before update on architecture_revisions
                for each row
                execute function riskweft_guard_architecture_revision_immutability();
        SQL);
    }

    public function down(): void
    {
        DB::statement('drop trigger if exists riskweft_architecture_revisions_immutability on architecture_revisions;');
        DB::statement('drop function if exists riskweft_guard_architecture_revision_immutability();');
    }
};

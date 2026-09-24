# ADR 0008 — Revision content immutability and dangling-reference prevention are enforced at the database layer, not only in application code

**Status:** Accepted for v1 (Phase 0)

## Decision
Two Phase 0 invariants that could otherwise be "just" an application-layer
convention are additionally enforced by PostgreSQL itself:

1. **Immutability** (Frozen v1 Invariant #1): `architecture_revisions` has
   no `updated_at` column, and a `BEFORE UPDATE` trigger
   (`riskweft_guard_architecture_revision_immutability`, added in
   `database/migrations/..._000012_add_immutability_guard_to_architecture_revisions.php`)
   rejects any change to a content-bearing column. Only `status` may change,
   reserved for the review-workflow state machine a later phase introduces.
2. **No dangling references** (`implementation-start/failure-catalog.json`):
   `revision_relationships.source_entity_row_id` /
   `target_entity_row_id` are real foreign keys into
   `revision_entities.id`, scoped to the same revision by construction
   (`EloquentArchitectureRevisionRepository::commit()` only ever wires a
   relationship to an entity row it just inserted in the same transaction).

`App\Domain\Architecture\ArchitectureDocumentValidator` still performs both
checks at the application layer first, so a client gets a clear
`ARCHITECTURE_DOCUMENT_INVALID` (422) response - the database layer is
defense-in-depth against an application-layer bug, not the primary UX.

## Consequences
Any future code path that legitimately needs to change revision status
(the only allowed post-commit mutation) must go through a normal `UPDATE
... SET status = ...`, which the trigger permits; any code path that tries
to touch `canonical_document`/`content_hash`/etc. after insert - however it
gets introduced - fails immediately and loudly in every environment,
including ones where an application-layer bug might otherwise have slipped
through code review.

## Revisit trigger
A future phase needs a legitimate, audited way to mutate committed
content (e.g. redaction for a legal/compliance takedown). That is a new,
deliberate, audited operation with its own ADR - never a quiet loosening
of this trigger.

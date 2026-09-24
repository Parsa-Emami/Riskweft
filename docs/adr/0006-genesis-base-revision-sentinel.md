# ADR 0006 — Genesis sentinel for a project's first commit's base_revision_id

**Status:** Accepted for v1 (Phase 0)

## Decision
`contracts/openapi.v1.yaml` declares `base_revision_id` as `required` with
`minLength: 1` on `POST /projects/{project}/revisions`, with no documented
null/absent case for a project's very first revision. Rather than silently
treating an empty or missing value as "no parent" (which would contradict
the frozen contract's `required` + `minLength: 1`), a client commits a
project's first revision by sending the literal string `"genesis"` as
`base_revision_id`. The HTTP layer
(`App\Http\Controllers\Api\V1\ArchitectureRevisionController`) translates
this sentinel to a real `null` before it reaches the Application/Domain
layers; `App\Domain\Architecture\ArchitectureRevision::GENESIS_BASE_REVISION_ID`
is the single source of truth for the literal value.

## Consequences
Every client integrating against v1 must know this sentinel to create a
project's first revision. It is documented in the sentinel constant's
doc-comment and in `docs/phase-0-exit-evidence.md`. If OpenAPI v2 (or a
minor additive v1 change, if the contract change process in
`spec/12_MIGRATION_UPGRADE_ROLLBACK.md` allows it) makes `base_revision_id`
nullable instead, this sentinel becomes unnecessary and should be removed
in the same change, not left as a dead alternate path.

## Revisit trigger
`contracts/openapi.v1.yaml` is amended to make `base_revision_id` nullable
or optional, or user research/integration feedback shows the sentinel is a
recurring source of integration bugs.

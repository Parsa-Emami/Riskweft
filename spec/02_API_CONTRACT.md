# RiskWeft — Final Implementation Specification

This specification freezes the v1 implementation contract. Changes that violate an invariant, authority boundary or public contract require an ADR/RFC and migration/compatibility plan.

## HTTP conventions
- Prefix `/api/v1`; JSON UTF-8; explicit content type.
- Resource IDs are opaque UUID/ULID-style identifiers and authorization is re-evaluated after lookup.
- Cursor pagination for unbounded collections.
- Mutation request/response schemas are versioned in OpenAPI; unknown security-sensitive fields are rejected where ambiguity is dangerous.
- Stable error codes follow the common error standard.

| Method | Route | Purpose | Gate |
|---|---|---|---|
| `POST` | `/projects/{project}/revisions` | Commit canonical architecture revision | auth + validation + idempotency where side-effecting |
| `GET` | `/revisions/{revision}` | Read immutable canonical model | auth + validation + idempotency where side-effecting |
| `POST` | `/revisions/{revision}/evaluate` | Deterministically evaluate rule packs | auth + validation + idempotency where side-effecting |
| `GET` | `/revisions/{a}/diff/{b}` | Semantic typed diff | auth + validation + idempotency where side-effecting |
| `POST` | `/merges` | Three-way semantic merge | auth + validation + idempotency where side-effecting |
| `POST` | `/imports` | Validate/import external model via adapter | auth + validation + idempotency where side-effecting |

## Compatibility
Additive compatible changes may ship in v1. Removing/renaming/changing semantics requires deprecation + migration and normally a new API major. Golden request/response fixtures run in CI.

# RiskWeft — Final Implementation Specification

This specification freezes the v1 implementation contract. Changes that violate an invariant, authority boundary or public contract require an ADR/RFC and migration/compatibility plan.

| Data class | v1 rule |
|---|---|
| revisions | immutable history until workspace retention deletion permits removal |
| reviews/audit | retain with revision provenance |
| imports | original artifact optional; canonical normalized content authoritative after accepted import |
| exports | reproducible, expirable artifacts; revision remains authority |

## Classification labels
`PUBLIC`, `INTERNAL`, `CONFIDENTIAL`, `SECRET`, plus project-specific `EVIDENCE`/`SECURITY_TELEMETRY`. Classification drives logging, export, encryption, backup and retention behavior.

## Deletion
Deletion is an explicit domain operation with authorization, hold checks, audit and downstream projection/cache invalidation. Derived stores must not resurrect deleted authoritative data during rebuild.

# RiskWeft — Final Implementation Specification

This specification freezes the v1 implementation contract. Changes that violate an invariant, authority boundary or public contract require an ADR/RFC and migration/compatibility plan.

## Golden E2E scenarios
1. Commit model and reload losslessly
2. Trust-boundary flow emits deterministic STRIDE threat
3. Same revision evaluates to same threat IDs
4. Concurrent stale edit returns conflict not overwrite
5. Semantic diff and merge preserves threat provenance

For every golden scenario record expected authoritative state, emitted event IDs/types, audit action, projection state, critical metrics and cleanup state. Run from a clean environment with deterministic synthetic fixtures. At least one scenario must fail mid-flight and then converge after restart/retry/reconciliation.

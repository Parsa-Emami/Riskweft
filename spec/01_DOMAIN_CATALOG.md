# RiskWeft — Final Implementation Specification

This specification freezes the v1 implementation contract. Changes that violate an invariant, authority boundary or public contract require an ADR/RFC and migration/compatibility plan.

## Domain objects
- `Project`
- `ArchitectureRevision`
- `Component`
- `DataFlow`
- `TrustBoundary`
- `Asset`
- `RulePack`
- `RuleIR`
- `Threat`
- `Control`
- `Requirement`
- `Review`
- `MergeConflict`
- `ExportArtifact`

## Aggregate rules
- Aggregate mutations occur through application commands, never controller/model convenience writes.
- Aggregate version is checked on conflicting writes.
- Invariants are tested with illegal-transition/negative cases.
- Domain objects do not import Laravel/Eloquent/vendor SDKs.

## Frozen v1 invariants
1. ArchitectureRevision is immutable once committed.
2. Canvas coordinates are presentation metadata and cannot change security semantics.
3. Every generated threat records exact rule-pack/version and input revision digest.
4. Merge never auto-resolves contradictory security semantics without an explicit deterministic rule.

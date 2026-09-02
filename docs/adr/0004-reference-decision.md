# ADR 0004 — RiskWeft reference decision

**Status:** Accepted for v1

## Decision
Semantic diff compares typed entities/relationships, not raw JSON text. Merge conflicts are explicit domain objects.

## Consequences
Implementation and tests must preserve this boundary. An alternative is allowed only through the relevant port/adapter when it preserves authority, public contracts, security invariants and migration semantics.

## Revisit trigger
Measured benchmark, security evidence, maintenance/licensing change or operational incident demonstrates the reference choice no longer satisfies the declared workload/threat model. Revisit via a new superseding ADR; never silently rewrite history.

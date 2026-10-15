# RiskWeft — Definitive Sequential Roadmap v7

**Portfolio order:** 1 / 10
**Complexity:** Medium
**Planning estimate:** 8–12 focused weeks (not a promise; recalibrate after Phase 1)

**Why this order:** Best first project: meaningful security domain, deterministic core, no privileged host runtime, and a demonstrable UI/API path.

## First executable proof
Canonical architecture revision → deterministic rules/STRIDE evaluation → provenance hash → identical replay result.

## Rules for this repository
- One phase at a time; no implementation work for phase N+1 before phase N exit evidence is merged.
- Contract/schema changes precede dependent implementation.
- Every retryable operation is idempotent.
- Every derived store/projection has a rebuild path.
- Negative and failure tests are part of the feature, not post-release hardening.
- Status claims must be evidence-backed.

## Phase 0 — Canonical model/schema

**Implementation deliverables**
- Bootstrap Laravel 13 API and PostgreSQL schema
- Define canonical architecture document/revision/node/edge/value objects
- Implement normalization and content hashing
- Implement optimistic concurrency/version preconditions

**Required verification**
- schema migration tests
- canonicalization golden tests
- hash stability tests
- concurrent edit conflict tests

**Definition of Done**
- The same logical architecture normalizes to the same canonical document and revision hash across repeated runs.
- CI is green from a clean checkout.
- New behavior is observable without logging secrets/sensitive payloads.
- Failure behavior and rollback/recovery note are documented.
- Contracts and README status are updated if externally visible behavior changed.

## Phase 1 — Canvas projection

**Implementation deliverables**
- Bootstrap React + TypeScript + Vite UI
- Build canvas as projection of canonical model, never source of truth
- Implement deterministic layout persistence separate from semantic model
- Add keyboard-accessible CRUD and validation feedback

**Required verification**
- component tests
- round-trip projection tests
- accessibility checks
- UI/API contract tests

**Definition of Done**
- Canvas edits round-trip without changing unrelated semantic content; keyboard-only editing covers the primary workflow.
- CI is green from a clean checkout.
- New behavior is observable without logging secrets/sensitive payloads.
- Failure behavior and rollback/recovery note are documented.
- Contracts and README status are updated if externally visible behavior changed.

## Phase 2 — RuleIR compiler + STRIDE pack

**Implementation deliverables**
- Define versioned RuleIR AST
- Compile rules to deterministic evaluator representation
- Implement initial STRIDE rule pack with stable rule IDs
- Add rule provenance and explainability payload

**Required verification**
- parser/compiler golden tests
- rule fixture tests
- deterministic ordering tests

**Definition of Done**
- Identical revision + rule-pack version yields byte-stable normalized findings and provenance metadata.
- CI is green from a clean checkout.
- New behavior is observable without logging secrets/sensitive payloads.
- Failure behavior and rollback/recovery note are documented.
- Contracts and README status are updated if externally visible behavior changed.

## Phase 3 — Threat/control workflow

**Implementation deliverables**
- Threat lifecycle: proposed/accepted/mitigated/false-positive
- Control catalog and traceable mappings
- Authorization rules for reviewer/author roles
- Audit every state transition

**Required verification**
- state machine tests
- authorization matrix tests
- audit completeness tests

**Definition of Done**
- No threat/control status change occurs outside defined transitions, and every transition is attributable and reviewable.
- CI is green from a clean checkout.
- New behavior is observable without logging secrets/sensitive payloads.
- Failure behavior and rollback/recovery note are documented.
- Contracts and README status are updated if externally visible behavior changed.

## Phase 4 — Semantic version/diff/merge

**Implementation deliverables**
- Semantic diff over model entities
- Three-way merge with explicit conflict objects
- Revision DAG and merge provenance
- No silent last-write-wins for semantic conflicts

**Required verification**
- merge property tests
- conflict fixture suite
- revision DAG integrity tests

**Definition of Done**
- Independent non-conflicting edits merge automatically; conflicting semantic edits remain explicit until resolved.
- CI is green from a clean checkout.
- New behavior is observable without logging secrets/sensitive payloads.
- Failure behavior and rollback/recovery note are documented.
- Contracts and README status are updated if externally visible behavior changed.

## Phase 5 — Import/export adapters + TM-BOM

**Implementation deliverables**
- Define adapter boundary
- Implement first import format and TM-BOM export
- Preserve unknown fields in quarantined extension area where safe
- Validate limits and reject ambiguous mappings

**Required verification**
- round-trip fixtures
- fuzz malformed imports
- TM-BOM schema validation

**Definition of Done**
- Supported formats round-trip within documented loss rules; unsupported semantics are reported rather than guessed.
- CI is green from a clean checkout.
- New behavior is observable without logging secrets/sensitive payloads.
- Failure behavior and rollback/recovery note are documented.
- Contracts and README status are updated if externally visible behavior changed.

## Phase 6 — Review/evidence/reporting

**Implementation deliverables**
- Evidence attachments with hashes
- Review snapshots pinned to exact revision/ruleset
- Deterministic report rendering inputs
- Export review bundle with verification manifest

**Required verification**
- evidence tamper tests
- report snapshot tests
- bundle verification tests

**Definition of Done**
- A reviewer can reproduce the exact findings/report from the pinned architecture revision and ruleset.
- CI is green from a clean checkout.
- New behavior is observable without logging secrets/sensitive payloads.
- Failure behavior and rollback/recovery note are documented.
- Contracts and README status are updated if externally visible behavior changed.

## Phase 7 — Scale/accessibility/public v1

**Implementation deliverables**
- Performance budgets for large models
- WCAG-oriented keyboard/focus/contrast audit
- Threat model and abuse-case closure
- Signed release/SBOM/provenance

**Required verification**
- load tests
- axe/browser a11y suite
- OWASP ASVS mapped tests
- clean install test

**Definition of Done**
- Public v1 meets documented size/latency budgets, core accessibility checks, and reproducible security/release gates.
- CI is green from a clean checkout.
- New behavior is observable without logging secrets/sensitive payloads.
- Failure behavior and rollback/recovery note are documented.
- Contracts and README status are updated if externally visible behavior changed.

## Public v1 invariant
The project is not called production-ready merely because all phases are coded. `PUBLIC-V1-GO` requires the repository release gate, clean-install proof, security/recovery evidence and signed/verifiable release artifacts.

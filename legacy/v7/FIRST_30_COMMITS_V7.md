# RiskWeft — First 30 Commit Plan v7

These are atomic intent boundaries, not mandatory commit-message text. Do not combine unrelated items merely to hit a number.

1. chore: initialize toolchain manifests and lockfiles
2. ci: make repository validation executable
3. chore: add local development environment/health checks
4. test: add clean-bootstrap smoke test
5. docs: freeze Phase 0 invariants and ADR index
6. phase0: bootstrap Laravel 13 API and PostgreSQL schema
7. phase0: define canonical architecture document/revision/node/edge/value objects
8. phase0: implement normalization and content hashing
9. phase0: implement optimistic concurrency/version preconditions
10. test(phase0): add schema migration tests
11. docs(phase0): record exit evidence and ADR updates
12. phase1: bootstrap React + TypeScript + Vite UI
13. phase1: build canvas as projection of canonical model, never source of truth
14. phase1: implement deterministic layout persistence separate from semantic model
15. phase1: add keyboard-accessible CRUD and validation feedback
16. test(phase1): add component tests
17. docs(phase1): record exit evidence and ADR updates
18. phase2: define versioned RuleIR AST
19. phase2: compile rules to deterministic evaluator representation
20. phase2: implement initial STRIDE rule pack with stable rule IDs
21. phase2: add rule provenance and explainability payload
22. test(phase2): add parser/compiler golden tests
23. docs(phase2): record exit evidence and ADR updates
24. phase3: threat lifecycle: proposed/accepted/mitigated/false-positive
25. phase3: control catalog and traceable mappings
26. phase3: authorization rules for reviewer/author roles
27. phase3: audit every state transition
28. test(phase3): add state machine tests
29. docs(phase3): record exit evidence and ADR updates
30. test: close Phase 0/1 regression or failure-proof gap before scope expansion

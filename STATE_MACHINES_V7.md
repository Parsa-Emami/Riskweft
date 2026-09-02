# RiskWeft — State Machines v7

- **Architecture:** `DRAFT -> REVIEW -> APPROVED -> SUPERSEDED`
- **Threat:** `OPEN -> MITIGATING -> MITIGATED | ACCEPTED | FALSE_POSITIVE`
- **Merge:** `PENDING -> RESOLVED | ABORTED`

Transitions are domain commands with authorization, optimistic version check where applicable, audit event and invariant tests. Illegal transitions fail closed and are observable.

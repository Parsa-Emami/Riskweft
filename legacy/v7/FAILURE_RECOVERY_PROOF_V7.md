# RiskWeft — Failure & Recovery Proof v7

## Mandatory injected failures
- malformed import
- dangling component reference
- rule-pack mismatch
- merge conflict
- stale revision write
- deleted entity referenced by threat
- schema upgrade

## Pass criteria
For every failure: no invariant violation; no unauthorized bypass; no unbounded retry storm; orphan resources are reconciled; user-visible state becomes correct; telemetry identifies cause; retry/cancel/manual recovery is documented; recovery test is automated where feasible.

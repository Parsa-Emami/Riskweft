# RiskWeft — Data Ownership v7

- **PostgreSQL:** canonical revisions, rule packs, threats, controls, reviews
- **Canvas state:** derived projection only
- **ObjectStore:** exports/import originals and reports

## Universal rules
- cache is never authority
- derived projections have checkpoint + rebuild + staleness metric
- secrets/evidence follow project-specific retention and redaction
- deletion does not rewrite immutable audit/custody history

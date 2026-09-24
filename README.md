# RiskWeft

> v7.0 status: **ARCHITECTURE-GO / BOOTSTRAP-GO / PHASE-0-CODE-COMPLETE (CI unrun - see below)**

This directory is a standalone GitHub repository starting point for RiskWeft. It contains the frozen architecture/specification, valid HTTP/event/configuration contracts, GitHub governance/security controls, and project-specific source bootstrap commands.

## Implementation status
Phase 0 ("Canonical model/schema", see `PHASE_PLAN_V7.md` / `ROADMAP_V7.md`)
is implemented at `apps/control-plane`. **Read `docs/phase-0-exit-evidence.md`
before trusting this status line further**: it was authored in a sandbox
with no PHP/Composer/PostgreSQL, so the code has been carefully reviewed
and cross-checked but never actually executed as PHP. `composer install &&
php artisan migrate && composer test` (or pushing to GitHub, where
`.github/workflows/backend.yml` does the same) is the next required step
before Phase 1 begins, per this file's own "one phase at a time" rule.

## Runtime boundary
- Laravel 13 API/control plane
- React 19.2 latest patched + TypeScript
- Vite 8.x
- PostgreSQL 18.6
- Valkey
- OpenTelemetry

## First executable proof
Canonical architecture revision → deterministic rules/STRIDE evaluation → provenance hash → identical replay result.

## Start
```bash
make validate
# verify the GitHub owner/module namespace first
make bootstrap
```

Read `ROADMAP_V7.md`, `PROJECT_START_CHECKLIST_V7.md`, `ACCEPTANCE_GATES_V7.md`, and `GITHUB_REPOSITORY_SETUP_V7.md` before the first code commit.

## Evidence rule
Architecture readiness is not production readiness. “S-tier”, “secure”, “resilient”, or performance claims require executable test/observability/failure/recovery/benchmark/release evidence.

## v7 portfolio position
Recommended implementation order: **1 / 10**. Best first project: meaningful security domain, deterministic core, no privileged host runtime, and a demonstrable UI/API path.

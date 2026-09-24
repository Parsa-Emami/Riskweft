# RiskWeft control plane (`apps/control-plane`)

Laravel 13 API implementing Phase 0 of `../../ROADMAP_V7.md`: the canonical
architecture document/revision model, deterministic canonicalization +
content hashing, and optimistic-concurrency commits.

**Before anything else, read `../../docs/phase-0-exit-evidence.md`.** This
code was authored without a working PHP/Composer/PostgreSQL environment and
has never actually been executed - the commands below are what turns
"carefully reviewed" into "actually verified".

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Set up PostgreSQL 18 locally (or point at any reachable instance) and tell
the app where it is. Configuration is via *secret references*, not raw env
vars (see `../../spec/11_CONFIGURATION_REFERENCE.md`); the `local` provider
maps `secret://local/postgres-dsn` to the `SECRET_LOCAL_POSTGRES_DSN` env
var already present in `.env.example` - edit its value to match your local
database.

```bash
php artisan migrate
composer test        # PHPUnit: tests/Unit + tests/Feature
composer pint:test    # code style
composer analyse      # Larastan static analysis
```

### Getting a bearer token to call the API locally

The frozen `../../contracts/openapi.v1.yaml` contract has no login/token
endpoint (production auth is an OIDC adapter, an infrastructure-phase
concern). For local development/testing, use the dev-only Artisan command:

```bash
php artisan riskweft:dev:seed-actor you@example.com "My Project" modeler
```

This prints a `project_id` and a bearer `Bearer <token>` you can use
immediately:

```bash
curl -X POST http://localhost:8000/api/v1/projects/<project_id>/revisions \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: $(uuidgen)" \
  -d '{
    "base_revision_id": "genesis",
    "architecture": {
      "metadata": {"name": "Example"},
      "entities": [{"id": "11111111-1111-4111-8111-111111111111", "kind": "component", "name": "API"}],
      "relationships": []
    }
  }'
```

## Layout

```
app/Domain/          framework-free domain model (no Laravel/Eloquent imports)
app/Application/      use-case orchestration (commands/handlers, queries/handlers)
app/Infrastructure/    Eloquent models, repositories, secret resolution, audit/outbox adapters
app/Http/              controllers, form requests, resources, middleware - thin HTTP glue only
app/Policies/          project-scoped RBAC
database/migrations/   Phase 0 schema (see ../../spec/05_DATABASE_SCHEMA_BLUEPRINT.md)
tests/Unit/            pure PHP, no framework boot (mirrors the Domain layer's own constraint)
tests/Feature/         full HTTP + DB integration tests
```

See `../../docs/adr/` for the decisions specific to this implementation
(0006-0008) alongside the frozen reference decisions (0001-0005).

# Phase 0 exit evidence — Canonical model/schema

Status: **code-complete, partially execution-verified**. See "What is NOT
yet verified" below before treating this as done in the sense
`ACCEPTANCE_GATES_V7.md` means by "evidence-backed". This document exists
because of `spec/16_SPRINT_ZERO_BACKLOG.md` first-30-commits item 11:
"docs(phase0): record exit evidence and ADR updates."

## Why this document is unusually explicit about environment limits

This phase was implemented in a sandboxed environment with **no PHP,
Composer, or PostgreSQL installed, and no network egress to install
them** (Packagist, apt and the npm registry are all unreachable from that
sandbox). Every PHP file in `apps/control-plane` was therefore hand-authored
and never executed by its author. `ROADMAP_V7.md`'s own rule -
"Status claims must be evidence-backed" - means that gap has to be stated
plainly rather than glossed over. What follows is exactly what was, and
was not, actually run.

## What was delivered

- `apps/control-plane`: a Laravel 13 API skeleton (hand-authored composer
  manifest, `bootstrap/app.php`, config, routes - no `composer create-project`
  scaffold was available to generate from).
- **Domain layer** (`app/Domain/Architecture`, zero framework imports):
  `ArchitectureDocument`, `ArchitectureEntity`, `ArchitectureRelationship`,
  `ArchitectureRevision`, `Canonicalizer`, `ContentHash`,
  `ArchitectureDocumentValidator`, `RevisionStatus`/`EntityKind`/
  `RelationshipKind`, and the domain exception hierarchy.
- **Application layer**: `CommitArchitectureRevisionCommand`/`Handler`,
  `GetArchitectureRevisionQuery`/`Handler` - the only code paths allowed to
  perform the Phase 0 mutation.
- **Infrastructure**: Eloquent models + migrations for `projects`,
  `architecture_revisions`, `revision_entities`, `revision_relationships`,
  `project_members`, `outbox_messages`, `audit_events`, `idempotency_keys`,
  `users`; `SecretReferenceResolver` (ADR 0007); repositories; audit logger;
  outbox publisher.
- **HTTP**: `POST /api/v1/projects/{project}/revisions` and
  `GET /api/v1/revisions/{revision}` exactly as
  `contracts/openapi.v1.yaml` declares them - bearer auth (Sanctum),
  project-scoped RBAC (`ProjectPolicy`), required Idempotency-Key with
  replay-or-reject semantics, `X-Request-ID` correlation, per-principal rate
  limiting, and a uniform `OperationResult`/`ApiError` envelope that never
  leaks a raw exception message or stack trace.
- **Tests**: `tests/Unit/Domain/Architecture/*` (canonicalization golden +
  hash-stability + reordering-invariance tests, validator negative cases,
  content-hash tests) and `tests/Feature/*` (full commit flow including
  auth/cross-scope-IDOR/conflict/idempotency/validation, read flow, schema
  migration tests, the immutability-trigger test, config-schema compliance,
  redaction, health check).
- **CI**: `.github/workflows/backend.yml` - PHP 8.3 + PostgreSQL 18 service
  container + Pint + Larastan + PHPUnit + the two Python cross-checks below.
- **ADRs 0006-0008** documenting the genesis sentinel, the local secret
  resolver scope, and the immutability/referential-integrity design.

## What is genuinely, executably verified right now

Nothing above could be run through PHP. What *was* actually run, in this
repository, in this sandbox:

| Check | Command | Result |
|---|---|---|
| PHP structural sanity (brace/heredoc balance, PSR-4 namespace match, no duplicate classes, every own-namespace `use` resolves) across all 100 files | `python3 scripts/php_sanity_check.py apps/control-plane` | **Pass** |
| Canonicalization/hashing determinism - an independent Python re-implementation of `Canonicalizer`/`ContentHash`, including the exact golden-fixture byte string pinned in `CanonicalizerTest.php` | `python3 scripts/verify-canonicalization.py` | **Pass, 9/9 checks** |
| `contracts/config.schema.json` vs. both `.env.example` files agree (the same assertions `ConfigSchemaComplianceTest.php` makes, re-run directly) | ad hoc Python during authoring | **Pass** |
| Repository-wide contract validation (pre-existing project tooling) | `python3 scripts/validate_repo.py` | **Pass** |

These are real, useful signals - they catch typos, structural mistakes and
algorithm-design errors - but they are **not** `php -l`, not
`composer install`, and not `phpunit`. Treat this phase as "should work,
carefully reviewed, never executed as PHP" until the table below is filled
in by an environment that actually has PHP.

## What is NOT yet verified (do this next)

None of the following has ever run:

- `composer install` (composer.json is a hand-authored manifest; there is
  **deliberately no `composer.lock`** - fabricating one with invented
  package hashes would be worse than no lock file at all, since it would
  look verified without being verified. Generate the real one yourself.)
- `php artisan migrate` against a real PostgreSQL 18 instance
- `vendor/bin/phpunit` / `composer test`
- `composer pint:test` (code style) / `composer analyse` (Larastan)

### How to actually verify it

```bash
cd apps/control-plane
composer install
cp .env.example .env
php artisan key:generate
# Create a local Postgres 18 database, then set SECRET_LOCAL_POSTGRES_DSN
# in .env to point at it (see the comments in .env.example).
php artisan migrate
composer test
composer pint:test
composer analyse
```

Or simplest: push this branch to GitHub. `.github/workflows/backend.yml`
does all of the above automatically against a disposable PostgreSQL 18
service container and is the authoritative "CI is green from a clean
checkout" signal `ROADMAP_V7.md` asks for.

**If anything above fails**, the most likely causes, in order, are: (1) a
version-constraint mismatch in `composer.json` now that real dependency
resolution is happening (Laravel 13 / Sanctum's exact released API is past
the knowledge available while authoring this), (2) a typo that
`scripts/php_sanity_check.py` cannot detect because it doesn't parse full
PHP grammar (only brace/heredoc balance and imports), or (3) a PostgreSQL
version-specific SQL detail in the raw trigger migration. None of these are
expected to be large; please open an issue/PR-comment with the exact error
rather than silently patching around it, so the fix can be reviewed against
the spec like everything else in this repository.

## Deliberately deferred (not forgotten - out of Phase 0's stated scope)

- The outbox **relay** worker (rows are written transactionally and proven
  by test; nothing yet drains `outbox_messages` to Valkey).
- `rule_packs` / `rule_pack_versions` / `threats` / `controls` /
  `requirements` / `reviews` / `merge_conflicts` / `exports` tables - these
  belong to Phases 2/3/4/5/6 respectively per `ROADMAP_V7.md`, and were not
  pre-created empty in Phase 0 to avoid guessing at schema that hasn't been
  designed yet.
- Revision status transitions beyond the `DRAFT` default (`REVIEW` /
  `APPROVED` / `SUPERSEDED` are modelled in the enum and the DB trigger
  already permits `status` to change, but no command/endpoint exercises
  this yet).
- A production secret-provider adapter (only `secret://local/...` exists -
  ADR 0007).
- OpenTelemetry span/metric emission code (config plumbing for
  `OTEL_EXPORTER_OTLP_ENDPOINT` exists; no instrumentation calls yet).
- A true multi-connection concurrency test. The automated test suite
  proves the *observable contract* (`base_revision_id` no longer matching
  the head -> `409 REVISION_CONFLICT`, never a silent overwrite) via a
  sequential two-client simulation, which is the standard, non-flaky way to
  test this property without process-forking in CI. Actually proving the
  underlying `SELECT ... FOR UPDATE` blocks a second *simultaneously live*
  connection is a manual/load-test verification step, not an automated one.

## Next phase

Per `ROADMAP_V7.md`'s "one phase at a time" rule, Phase 1 (Canvas
projection) should not begin until the verification steps above have
actually been run against this code and any resulting fixes are in.

## CI feedback — round 1

The first real `.github/workflows/backend.yml` run (the first time this
code was ever actually executed as PHP) failed at `composer install`,
exactly the kind of gap the "What is NOT yet verified" section above
warned about. Recorded here per `ROADMAP_V7.md` "status claims must be
evidence-backed" - fixes were driven by current web research, not
guessing, since this assistant's reliable knowledge predates these
package releases:

- **Root cause**: `larastan/larastan: ^2.9` cannot resolve against
  `laravel/framework: ^13.0` - larastan 2.x's latest release only supports
  `illuminate/support` up to `^11.51`, and `laravel/framework` 13.x bundles
  (`replaces`) `illuminate/support` at `v13.x`. larastan 3.x is the line
  that added Laravel 13 support (`illuminate/support: ^11.44.2 || ^12.4.1 ||
  ^13`), which in turn requires `phpstan/phpstan: ^2.2.14`.
- **Fixed in `composer.json`**: `larastan/larastan` `^2.9` → `^3.0`;
  `phpstan/phpstan` `^1.11` → `^2.2`; `phpunit/phpunit` `^11.0` → `^12.0`;
  `laravel/tinker` `^2.9` → `^3.0` (Laravel 13's own upgrade guide lists
  this exact set as its "High Impact: updating dependencies" item). Added
  `laravel/pail` as a dev dependency to match the current default skeleton.
- **Added `phpstan.neon`**: this file did not exist at all, which would
  have failed the very next CI step (`composer analyse`) even after the
  resolution fix. Includes `larastan/larastan`'s extension, starts at
  level 5 deliberately (see the file's own comment - ratchet up once a
  real report has been reviewed, not blind).
- **Fixed `tests/Feature/Schema/SchemaMigrationTest.php`**: the docblock
  `@dataProvider` annotation is removed in PHPUnit 12 (deprecated since 11,
  annotations for metadata removed entirely in 12) - converted to the
  `#[DataProvider('...')]` PHP attribute.
- **Precautionary, not CI-blocking, but fixed while in the neighbourhood**
  (researched against Laravel 13's actual documented breaking-change list):
  - `config/sanctum.php`'s CSRF middleware entry now references
    `PreventRequestForgery` (13's rename of `VerifyCsrfToken`) instead of
    the deprecated alias. Dead code either way for this app -
    `'stateful' => []` means Sanctum never applies it - but kept current.
  - `config/cache.php` gained `'serializable_classes' => false`, matching
    13's new deserialization-hardening default (this app never caches PHP
    objects, so this is a no-op safety net, not a fix for an active bug).
  - Added the four standard Laravel config files that were missing
    entirely (`session.php`, `filesystems.php`, `mail.php`,
    `services.php`) - none of Phase 0's routes exercise them, but their
    absence meant `config('session.*')` etc. would have resolved to `null`
    instead of real values if any framework/package boot path touched them,
    which is exactly the kind of thing that surfaces as a confusing crash
    only in a real environment, not in the static checks this sandbox could
    run.
- **Re-verified after fixing**: `scripts/php_sanity_check.py` (104 files,
  clean), `scripts/validate_repo.py` (clean), `scripts/verify-canonicalization.py`
  (9/9, unaffected - these fixes were all toolchain/config, not domain logic).
  `composer install`/`phpunit`/`pint`/`phpstan` still cannot be executed in
  this sandbox, so this round's fixes are, like Phase 0 itself, corrected
  by research and static review rather than by a green run - the next
  actual CI run is still the first real confirmation.

## CI feedback — round 2

`composer install` passed this time (round 1's fix held), and
`php artisan migrate:fresh` actually ran against a real PostgreSQL 18
service container for the first time - and got through 3 of 12 migrations
before failing:

```
BadMethodCallException: Method Illuminate\Database\Schema\Blueprint::check does not exist.
```

**This one is not a version-drift issue like round 1 - it's a plain
authoring mistake.** Laravel's fluent Schema Builder has never had a
`Blueprint::check()` method, at any version; raw CHECK constraints have
always required dropping to `DB::statement()`. Five call sites across four
migrations (`architecture_revisions`, `revision_entities`,
`revision_relationships` (two constraints), `project_members`) used the
nonexistent fluent method. All five are fixed the same way: the
`Schema::create()` closure keeps every column/index/foreign-key
definition, and each CHECK constraint moves to its own
`DB::statement("alter table ... add constraint ... check (...)")` call
immediately after the closure, inside the same `up()`. `down()` is
unchanged (`dropIfExists` already drops the constraints with the table).

This is exactly the category of bug `scripts/php_sanity_check.py` cannot
catch (it checks brace balance and imports, not whether a called method
actually exists on a class) - real execution against real PostgreSQL is
what found it, on the very first migration that used the broken pattern.
The other three tables that also carry CHECK constraints in
`spec/05_DATABASE_SCHEMA_BLUEPRINT.md` intent were never reached by this
run, but used the identical wrong pattern and are fixed pre-emptively
rather than waiting for another failing run to find each one in turn.

Re-verified: `scripts/php_sanity_check.py` (clean), `scripts/validate_repo.py`
(clean), `scripts/verify-canonicalization.py` (9/9, unaffected). Migrations
5 through 12 have still never actually executed - the next CI run is the
first real test of all of them, including the immutability trigger
migration, which is the piece of this phase with the least any-form-of
verification behind it so far.

## CI feedback — round 3

`php artisan migrate:fresh` progressed one migration further this time
(round 2's fix held: the CHECK constraint on `architecture_revisions`
itself was never reached) and failed on the very next statement in the
same migration:

```
SQLSTATE[42830]: Invalid foreign key: 7 ERROR: there is no unique
constraint matching given keys for referenced table "architecture_revisions"
(... alter table "architecture_revisions" add constraint
"architecture_revisions_parent_revision_id_foreign" foreign key
("parent_revision_id") references "architecture_revisions" ("id") ...)
```

**Root cause**: `parent_revision_id` is a *self-referencing* foreign key -
`architecture_revisions` pointing at its own `id`. On PostgreSQL, adding a
self-referencing foreign key as part of the *same* `Schema::create()`
blueprint that also defines the primary key it points at fails this way:
the ALTER TABLE that adds the constraint is compiled and executed before
Postgres has finished establishing the table's own primary key as a
constraint it can match a foreign key against. This is a documented,
fairly common Laravel-on-PostgreSQL gotcha, confirmed against other
developers' reports of the identical error and error code for the
identical pattern (a nullable `parent`-style self-reference added inline).
It did not surface for `projects.head_revision_id -> architecture_revisions.id`
because that one was *already* split into its own migration
(`..._000007_add_head_revision_foreign_key_to_projects_table.php`) for an
unrelated reason (avoiding a circular forward reference between two
different tables) - the self-referencing case within a single table needs
the identical split, which it hadn't gotten.

**Fix**: `parent_revision_id`'s foreign key moved out of the
`Schema::create('architecture_revisions', ...)` closure into its own
`Schema::table('architecture_revisions', ...)` call immediately after -
same file, same migration, same `up()` method, just no longer inline. By
the time that second call runs, the table (and its primary key) is fully,
separately established. Every other migration was audited for the same
pattern (a table referencing itself inside its own `Schema::create()`
block, via either `->constrained()` or `->foreign()->references()->on()`)
and none exists - `architecture_revisions` was the only self-referencing
table in this phase's schema, so this is a complete fix, not a partial one
awaiting the next failure.

Re-verified: `scripts/php_sanity_check.py` (clean), `scripts/validate_repo.py`
(clean), `scripts/verify-canonicalization.py` (9/9, unaffected), plus a new,
purpose-built check (self-reference scan across every migration's
`Schema::create()` block) confirming no other table has this issue.
Migrations 5 through 12 - including the immutability trigger, still the
least-verified piece of this phase - remain genuinely untested by real
execution; the next CI run is still the first real look at them.

## CI feedback — round 4

`php artisan migrate:fresh` passed **completely** this time - all 12
migrations, including the immutability trigger (the least-verified piece
of this phase up to this point) - genuinely ran and succeeded against real
PostgreSQL 18. This is the first fully-green schema in this phase's
history. The run then reached `composer pint:test` (code style) and failed
with 27 style violations across 27 of 104 files.

This round is different in kind from rounds 1-3: every issue is cosmetic
(formatting only, zero behavioural risk), and about half were resolved
with full confidence by fetching Laravel Pint's actual preset source
(`laravel/pint` `resources/presets/laravel.php`) and, for two ambiguous
rule names, PHP-CS-Fixer's own rule documentation with concrete
before/after examples - not guessed:

- **`declare_strict_types` + `blank_line_after_opening_tag`** (18 files:
  every file under `bootstrap/`, `config/`, `public/`, `routes/`) - these
  framework/config files never got the `<?php\n\ndeclare(strict_types=1);\n\n`
  header this codebase's own `app/` files consistently use. Applied
  uniformly.
- **`fully_qualified_strict_types`** (7 files) - inline
  `Some\Fully\Qualified\ClassName` references (both with and without a
  leading `\`) converted to a `use` import + short name, matching the
  preset's `'fully_qualified_strict_types' => ['import_symbols' => true, ...]`
  config. Also removed one now-provably-unused `use Laravel\Sanctum\Sanctum;`
  found while fixing `config/sanctum.php`.
- **`phpdoc_align`** (2 files) - multi-line `@throws` descriptions with a
  hand-wrapped, fixed-width continuation indent were restructured: the
  explanation moved into the docblock's prose, leaving `@throws
  ExceptionType` with no trailing description on either file. Confirmed
  against the preset's `'phpdoc_align' => ['align' => 'left', ...]`.
- **`ordered_imports`**: found and fixed one real case
  (`Illuminate\Auth\AuthenticationException` was listed before
  `Illuminate\Auth\Access\AuthorizationException` - wrong under
  alphabetical/`alpha` sort) in `ApiExceptionRenderer.php`, then wrote a
  script to check every import block in the whole tree under both
  case-sensitive and case-insensitive comparison - no others exist.
- **`integer_literal_case`** (`Uuid.php`) - confirmed via PHP-CS-Fixer's own
  documented example (`0Xff` -> `0xFF`: lowercase hex *prefix*, uppercase hex
  *digits*) that `0x0f`/`0x3f` needed to become `0x0F`/`0x3F`.
- **`single_quote`**-class issue (`StaleRevisionWriteException.php`,
  reported truncated as `single_...`) - found by writing a script that
  scans every double-quoted string literal in the tree for one with no
  interpolation, no embedded single quote, and no meaningful escape
  sequence (all of which legitimately exempt a string from this rule);
  exactly one true positive turned up and was converted to single quotes.

**Two items were investigated at length but left unchanged, deliberately,
rather than guessed at further:**

- `Uuid.php`'s `braces_position`/`single_line_empty_body`-class flag on
  its one-line empty constructor (`private function __construct(...) {}`).
  PHP-CS-Fixer's own documented example for `single_line_empty_body`
  confirms this exact shape (`) {}` - signature, single space, `{}`) *is*
  the target format, which is what this file already has.
- `SecretReferenceResolver.php`'s `unary_operator_spac...` flag. The
  Laravel preset separately enables `not_operator_with_successor_space`
  (confirmed present in the fetched preset source), which specifically
  governs `!` spacing and wants a trailing space - matching this file's
  existing `! str_starts_with(...)` / `! isset(...)`. No other unary
  operator (`+`, `-`, `++`, `--`, `~`) appears anywhere in the file.

For both, the current code matches this assistant's best researched
understanding of the target format. If either is still flagged on the next
run, that is the clearest sign yet that hand-verifying Pint's exact
byte-level output without ever running Pint has reached its limit - the
reliable fix at that point is `vendor/bin/pint` (write mode, not
`--test`) run once, locally or as a throwaway CI step, which is now a
realistic, low-cost option since `composer install` and every migration
have both been proven to work in the real CI environment.

Re-verified: `scripts/php_sanity_check.py` (clean), `scripts/validate_repo.py`
(clean), `scripts/verify-canonicalization.py` (9/9, unaffected). PHPUnit,
Pint (for real, not by hand) and Larastan have still never executed - all
three remain the next real unknowns.

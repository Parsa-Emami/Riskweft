# Preserved optional API: CI repair

The previous `control-plane` workflow stopped in Pint at SecretReferenceResolver.php. The original style check, level-5 static analysis, PostgreSQL integration tests, canonicalization cross-check and repository validation remain enabled.

Pint formatting now passes. PHPStan 2 obsolete configuration options were removed; no analysis baseline or broad ignore was added. Secret resolution receives an explicit environment lookup from config/database.php, so framework environment reads remain in configuration and the resolver rejects missing/non-string values. Model read annotations reflect the existing enum, array and immutable datetime casts; a unit regression checks those actual casts. Composer dependencies are locked for repeatable clean installs. CI actions use immutable SHAs and checkout does not persist credentials.

Local PHP 8.3.6 validation passed Pint, level-5 Larastan and 26 unit tests (42 assertions). PostgreSQL feature results must be read from the GitHub Actions run for the actual pushed commit. No full integration result or complete product readiness is inferred from unit checks. The supported default full-product plan remains the browser-local TypeScript/IndexedDB profile; this optional Laravel API is preserved.

No database schema, migration, .env or Dockerfile was modified. Source-bound command results are in docs/evidence/oss-roadmap.json.

The first follow-up CI run passed migrations, Pint and static analysis, then exposed two additional pre-existing defects: Laravel guessed factory classes from the nested model namespace, and the Sanctum web guard fell back to itself. Explicit UseFactory attributes bind the existing factories; bearer-only Sanctum has no session fallback. Regression checks resolve/build both real model factories and prove unauthenticated guards remain guests without database access.

The same run exposed generated Laravel bootstrap caches in the source checksum manifest. scripts/hashes.py now enumerates Git-selected source rather than ignored dependency/runtime caches. A real clean-clone regression proves that caches do not affect integrity and modified source still fails validation. No assertion or security check is disabled. PostgreSQL integration results remain tied to the actual CI run and are not inferred from these local tests.

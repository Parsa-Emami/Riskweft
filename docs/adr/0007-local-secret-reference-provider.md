# ADR 0007 — "local" secret reference provider for DATABASE_DSN_REF / VALKEY_DSN_REF

**Status:** Accepted for v1 (Phase 0), local/dev/CI scope only

## Decision
`spec/11_CONFIGURATION_REFERENCE.md` defines `DATABASE_DSN_REF` /
`VALKEY_DSN_REF` as secret *references* ("never embed production
credentials in config"), not raw connection strings, but does not specify
a resolver. Phase 0 implements exactly one provider scheme,
`secret://local/<name>`, resolved by
`App\Infrastructure\Config\SecretReferenceResolver` to the environment
variable `SECRET_LOCAL_<UPPER_SNAKE_NAME>`. Any other provider (a real
vault, cloud secrets manager, ...) raises immediately rather than
guessing, and is explicitly out of scope until an infrastructure phase
implements it (the repository's `infra/` and `security/` directories are
still empty placeholders).

## Consequences
This resolver is safe for local development, the test suite and CI - never
for a staging/production deployment, which has no working secret resolver
yet. Deploying this code as-is outside local/CI will fail loudly (a clear
`RuntimeException`) rather than silently falling back to something
insecure, which is the intended behaviour until a real provider adapter
lands.

## Revisit trigger
An infrastructure phase adds a real secret-provider adapter (e.g. Vault,
AWS/GCP/Azure secrets manager). That adapter should implement the same
`SecretReferenceResolver::resolve()` contract for its own scheme prefix
rather than replacing this one, so local/CI continue to work unchanged.

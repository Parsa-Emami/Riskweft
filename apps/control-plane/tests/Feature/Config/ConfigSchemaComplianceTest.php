<?php

declare(strict_types=1);

namespace Tests\Feature\Config;

use Tests\TestCase;

/**
 * Keeps apps/control-plane/.env.example, the repository-root .env.example
 * and contracts/config.schema.json from silently drifting apart
 * (spec/11_CONFIGURATION_REFERENCE.md: "Canonical machine-readable source:
 * ../contracts/config.schema.json"). A small, explicitly named allow-list
 * covers the handful of keys that exist purely to wire Laravel itself and
 * are intentionally outside RiskWeft's documented business config surface.
 */
final class ConfigSchemaComplianceTest extends TestCase
{
    /** Framework-only keys, never part of contracts/config.schema.json. */
    private const FRAMEWORK_ONLY_KEYS = [
        'APP_KEY', 'APP_DEBUG', 'APP_URL',
        'CACHE_STORE', 'SESSION_DRIVER', 'QUEUE_CONNECTION',
        'SECRET_LOCAL_POSTGRES_DSN', 'SECRET_LOCAL_VALKEY_DSN',
    ];

    public function test_every_documented_business_config_key_in_env_example_is_in_the_schema(): void
    {
        $schema = $this->loadConfigSchema();
        $schemaKeys = array_keys($schema['properties']);

        foreach ($this->parseEnvExample() as $key => $value) {
            if (in_array($key, self::FRAMEWORK_ONLY_KEYS, true)) {
                continue;
            }

            self::assertContains($key, $schemaKeys, "env key [{$key}] is not declared in contracts/config.schema.json");
        }
    }

    public function test_every_schema_required_key_is_present_in_env_example(): void
    {
        $schema = $this->loadConfigSchema();
        $envKeys = array_keys($this->parseEnvExample());

        foreach ($schema['required'] as $requiredKey) {
            self::assertContains($requiredKey, $envKeys, "schema-required key [{$requiredKey}] is missing from apps/control-plane/.env.example");
        }
    }

    public function test_schema_still_rejects_unknown_top_level_keys(): void
    {
        $schema = $this->loadConfigSchema();

        self::assertFalse($schema['additionalProperties'] ?? true, 'contracts/config.schema.json must keep additionalProperties: false');
    }

    /** @return array<string, mixed> */
    private function loadConfigSchema(): array
    {
        $path = base_path('../../contracts/config.schema.json');
        self::assertFileExists($path);

        return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @return array<string, string> */
    private function parseEnvExample(): array
    {
        $path = base_path('.env.example');
        self::assertFileExists($path);

        $values = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || ! str_contains($trimmed, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $trimmed, 2);
            $values[trim($key)] = trim($value);
        }

        return $values;
    }
}

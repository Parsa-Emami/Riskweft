<?php

// RiskWeft business configuration. Every key here is sourced from an
// environment variable documented in ../../spec/11_CONFIGURATION_REFERENCE.md
// and validated by ../../contracts/config.schema.json - see
// tests/Feature/Config/ConfigSchemaComplianceTest.php. Do not add a new key
// here without adding it to config.schema.json first (contract-before-code,
// per ROADMAP_V7.md).
return [
    'service_name' => env('SERVICE_NAME', 'riskweft'),

    'hash_algorithm' => env('CANONICAL_HASH_ALGORITHM', 'sha256'),

    'ruleset_path' => env('RULESET_PATH', base_path('rules')),

    'max_architecture_bytes' => (int) env('MAX_ARCHITECTURE_BYTES', 2097152),
];

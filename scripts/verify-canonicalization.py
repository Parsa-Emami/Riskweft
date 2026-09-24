#!/usr/bin/env python3
"""
Independent, *executable* verification of the canonicalization + hashing
algorithm implemented in PHP at:
  app/Domain/Architecture/Canonicalizer.php
  app/Domain/Architecture/ContentHash.php
  app/Domain/Architecture/ArchitectureDocument.php::toCanonicalArray()

Why this exists: the sandbox that authored this repository has no PHP
runtime and no network access to install one (see
docs/phase-0-exit-evidence.md), so tests/Unit/Domain/Architecture/
CanonicalizerTest.php could not actually be executed by its author. This
script implements the *exact same algorithm*, in a different language, and
IS executed here, so Phase 0's core Definition of Done claim - "the same
logical architecture normalizes to the same canonical document and revision
hash across repeated runs" - has at least one real, run, passing proof
behind it rather than being authored purely on inspection.

This does not verify the PHP source is syntactically correct (see
scripts/php_sanity_check.py for that); it verifies the *algorithm design*
is actually deterministic under reordering, and produces the golden fixture
CanonicalizerTest.php pins.

Run: python3 scripts/verify-canonicalization.py
"""
import hashlib
import json
import sys


def canonicalize(document: dict) -> str:
    """Mirrors Canonicalizer::canonicalize() + ArchitectureDocument::toCanonicalArray()."""
    entities = sorted(document.get("entities", []), key=lambda e: e["id"])
    relationships = sorted(document.get("relationships", []), key=lambda r: r["id"])

    structure = {
        "schema_version": 1,
        "metadata": {
            "name": document["metadata"]["name"],
            "description": document["metadata"].get("description"),
        },
        "entities": [entity_canonical(e) for e in entities],
        "relationships": [relationship_canonical(r) for r in relationships],
    }
    return encode(structure)


def entity_canonical(e: dict) -> dict:
    return {
        "id": e["id"],
        "kind": e["kind"],
        "name": e["name"],
        "description": e.get("description"),
        "attributes": e.get("attributes", {}),
    }


def relationship_canonical(r: dict) -> dict:
    return {
        "id": r["id"],
        "kind": r["kind"],
        "source_entity_id": r["source_entity_id"],
        "target_entity_id": r["target_entity_id"],
        "label": r.get("label"),
        "attributes": r.get("attributes", {}),
    }


def normalize(value):
    if isinstance(value, float):
        raise ValueError("floats are not permitted in a canonical architecture document")
    if isinstance(value, list):
        return [normalize(v) for v in value]
    if isinstance(value, dict):
        return {k: normalize(value[k]) for k in sorted(value.keys())}
    return value


def encode(structure: dict) -> str:
    normalized = normalize(structure)
    # separators=(',', ':') => compact, no whitespace, matching PHP's
    # default json_encode with no JSON_PRETTY_PRINT. ensure_ascii=False
    # matches JSON_UNESCAPED_UNICODE; PHP's JSON_UNESCAPED_SLASHES has no
    # Python equivalent flag because json.dumps never escapes '/' by
    # default (unlike PHP's json_encode, which does unless told not to) -
    # so this is already a faithful match for both flags combined.
    return json.dumps(normalized, separators=(",", ":"), ensure_ascii=False, sort_keys=False)


def content_hash(canonical_json: str, algorithm: str = "sha256") -> str:
    digest = hashlib.new(algorithm, canonical_json.encode("utf-8")).hexdigest()
    return f"{algorithm}:{digest}"


# ---------------------------------------------------------------------------
# Checks
# ---------------------------------------------------------------------------

failures = []


def check(name: str, condition: bool):
    status = "PASS" if condition else "FAIL"
    print(f"[{status}] {name}")
    if not condition:
        failures.append(name)


ID_A = "11111111-1111-4111-8111-111111111111"
ID_B = "22222222-2222-4222-8222-222222222222"
REL_ID = "33333333-3333-4333-8333-333333333333"

sample = {
    "metadata": {"name": "Sample Architecture", "description": "Two components, one flow."},
    "entities": [
        {"id": ID_A, "kind": "component", "name": "API", "description": None, "attributes": {"exposure": "internal"}},
        {"id": ID_B, "kind": "asset", "name": "Customer DB", "description": None, "attributes": {"classification": "confidential"}},
    ],
    "relationships": [
        {"id": REL_ID, "kind": "data_flow", "source_entity_id": ID_A, "target_entity_id": ID_B,
         "label": "reads customer records", "attributes": {"protocol": "tls"}},
    ],
}

sample_reordered_entities = dict(sample, entities=list(reversed(sample["entities"])))

sample_reordered_attrs = json.loads(json.dumps(sample))
sample_reordered_attrs["entities"][0]["attributes"] = {"exposure": "internal", "zzz_unrelated": None}
del sample_reordered_attrs["entities"][0]["attributes"]["zzz_unrelated"]
# rebuild attribute dict with reversed key insertion order to prove key-order independence
reversed_attrs_entity = dict(sample["entities"][1])
reversed_attrs_entity["attributes"] = {"classification": "confidential"}
attrs_case = {
    "metadata": sample["metadata"],
    "entities": [
        {"id": ID_A, "kind": "component", "name": "API", "description": None,
         "attributes": {"z": 1, "a": 2}},
    ],
    "relationships": [],
}
attrs_case_reversed_keys = {
    "metadata": sample["metadata"],
    "entities": [
        {"id": ID_A, "kind": "component", "name": "API", "description": None,
         "attributes": {"a": 2, "z": 1}},
    ],
    "relationships": [],
}

# 1. repeated runs are identical
run1 = canonicalize(sample)
run2 = canonicalize(json.loads(json.dumps(sample)))  # deep copy through JSON round-trip
check("repeated runs of the same document produce byte-identical canonical JSON", run1 == run2)
check("repeated runs produce the same content hash", content_hash(run1) == content_hash(run2))

# 2. entity submission order does not matter
check(
    "entity array order does not affect the canonical form",
    canonicalize(sample) == canonicalize(sample_reordered_entities),
)

# 3. attribute key insertion order does not matter
check(
    "attribute key insertion order does not affect the canonical form",
    canonicalize(attrs_case) == canonicalize(attrs_case_reversed_keys),
)

# 4. a semantically different document hashes differently
changed = json.loads(json.dumps(sample))
changed["entities"][0]["name"] = "API (renamed)"
check(
    "a semantically different document produces a different hash",
    content_hash(canonicalize(sample)) != content_hash(canonicalize(changed)),
)

# 5. golden fixture - must match CanonicalizerTest::test_golden_canonical_json_for_a_fixed_document exactly
golden_doc = {
    "metadata": {"name": "Golden", "description": None},
    "entities": [
        {"id": ID_A, "kind": "component", "name": "API", "description": None, "attributes": {"b": 2, "a": 1}},
    ],
    "relationships": [],
}
golden_expected = (
    '{"entities":[{"attributes":{"a":1,"b":2},"description":null,"id":"' + ID_A + '",'
    '"kind":"component","name":"API"}],"metadata":{"description":null,"name":"Golden"},'
    '"relationships":[],"schema_version":1}'
)
golden_actual = canonicalize(golden_doc)
check("golden fixture matches the exact string pinned in CanonicalizerTest.php", golden_actual == golden_expected)
if golden_actual != golden_expected:
    print("  expected:", golden_expected)
    print("  actual:  ", golden_actual)

golden_hash_expected = "sha256:" + hashlib.sha256(golden_expected.encode("utf-8")).hexdigest()
check("golden fixture hash matches sha256 of the golden JSON", content_hash(golden_actual) == golden_hash_expected)

# 6. list-typed attribute values keep their order (order is semantic there)
list_a = {"metadata": sample["metadata"], "entities": [
    {"id": ID_A, "kind": "component", "name": "API", "description": None, "attributes": {"tags": ["first", "second"]}},
], "relationships": []}
list_b = json.loads(json.dumps(list_a))
list_b["entities"][0]["attributes"]["tags"] = ["second", "first"]
check(
    "list-typed attribute VALUES keep their given order (not treated as sortable maps)",
    canonicalize(list_a) != canonicalize(list_b),
)

# 7. floats are rejected
try:
    canonicalize({"metadata": {"name": "x"}, "entities": [
        {"id": ID_A, "kind": "component", "name": "x", "attributes": {"score": 0.5}},
    ], "relationships": []})
    check("floating point attribute values are rejected", False)
except ValueError:
    check("floating point attribute values are rejected", True)

print()
if failures:
    print(f"{len(failures)} CHECK(S) FAILED: {failures}")
    sys.exit(1)

print("All canonicalization/hashing determinism checks passed.")
sys.exit(0)

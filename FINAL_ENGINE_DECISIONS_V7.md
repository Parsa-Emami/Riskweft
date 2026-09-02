# RiskWeft — Final Engine Decisions v7

These are accepted v1 decisions, not open blockers. Alternatives require a future ADR and must preserve public/domain contracts.

1. Canonical truth is an immutable ArchitectureRevision document in PostgreSQL JSONB with stable UUIDs; the canvas is only a projection.
2. Rule packs are YAML/JSON declarative documents compiled to a deterministic RuleIR implemented in the Laravel domain layer; arbitrary code is prohibited.
3. Optimistic concurrency is v1 collaboration. CRDT/live co-editing is explicitly post-v1 and cannot redefine the canonical model.
4. Semantic diff compares typed entities/relationships, not raw JSON text. Merge conflicts are explicit domain objects.
5. Threats store rule-pack ID/version, matched facts, input revision hash and mitigation provenance.
6. CycloneDX TM-BOM / Threat Dragon / other threat-model formats are import-export adapters only. The internal schema never depends on unfinished external semantics.
7. STRIDE is bundled as the reference open rule pack; additional methodologies are plugin packs behind the same RuleIR.

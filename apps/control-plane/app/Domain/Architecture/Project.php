<?php

declare(strict_types=1);

namespace App\Domain\Architecture;

/**
 * spec/01_DOMAIN_CATALOG.md domain object `Project`: the scope that owns an
 * append-only chain of ArchitectureRevisions. Phase 0 keeps this minimal -
 * workspace/organization nesting is not yet modelled because nothing in the
 * Phase 0-7 roadmap requires it yet; adding a parent scope later is an
 * additive column, not a breaking one.
 */
final class Project
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $headRevisionId,
        public readonly int $headVersion,
    ) {}
}

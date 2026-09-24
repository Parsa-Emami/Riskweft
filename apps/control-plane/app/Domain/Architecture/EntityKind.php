<?php

declare(strict_types=1);

namespace App\Domain\Architecture;

/**
 * Node kinds in the canonical architecture document.
 * spec/01_DOMAIN_CATALOG.md domain objects: Component, TrustBoundary, Asset.
 */
enum EntityKind: string
{
    case Component = 'component';
    case TrustBoundary = 'trust_boundary';
    case Asset = 'asset';
}

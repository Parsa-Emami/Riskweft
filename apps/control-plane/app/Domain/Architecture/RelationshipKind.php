<?php

declare(strict_types=1);

namespace App\Domain\Architecture;

/**
 * Edge kinds in the canonical architecture document.
 *
 * - DataFlow: spec/01_DOMAIN_CATALOG.md domain object `DataFlow`, an edge
 *   between two components/assets.
 * - TrustBoundaryMembership: places a component/asset inside a trust
 *   boundary. Modelled as an edge (rather than a parent-entity pointer) so
 *   Phase 4 semantic diff/merge can treat boundary membership the same way
 *   it treats any other typed relationship (Final Engine Decision #4).
 */
enum RelationshipKind: string
{
    case DataFlow = 'data_flow';
    case TrustBoundaryMembership = 'trust_boundary_membership';
}

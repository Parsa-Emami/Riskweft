<?php

declare(strict_types=1);

namespace App\Domain\Architecture;

/**
 * STATE_MACHINES_V7.md - Architecture: DRAFT -> REVIEW -> APPROVED -> SUPERSEDED.
 *
 * Phase 0 only ever creates a revision in DRAFT status; the transition
 * commands (and their authorization/audit rules) are introduced with the
 * review workflow (ROADMAP_V7.md Phase 3 / Phase 6). The column and enum
 * exist now so the schema does not change shape under later phases
 * (spec/12_MIGRATION_UPGRADE_ROLLBACK.md - "contract/schema changes precede
 * dependent implementation").
 */
enum RevisionStatus: string
{
    case Draft = 'DRAFT';
    case Review = 'REVIEW';
    case Approved = 'APPROVED';
    case Superseded = 'SUPERSEDED';
}

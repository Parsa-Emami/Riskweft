<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RBAC membership: which role (spec/04_AUTHORIZATION_MATRIX.md - viewer,
 * modeler, reviewer, approver, workspace_admin) a user holds on a project.
 * Deeper policy (step-up approval, resource-state-aware rules) is layered
 * on top of this in later phases; Phase 0 only needs coarse role checks for
 * its two endpoints (see app/Policies/ArchitectureRevisionPolicy.php).
 */
final class ProjectMemberModel extends Model
{
    use HasUuids;

    protected $table = 'project_members';

    public $timestamps = false;

    protected $fillable = ['project_id', 'user_id', 'role'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectModel::class, 'project_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}

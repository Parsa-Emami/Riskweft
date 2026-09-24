<?php

declare(strict_types=1);

namespace App\Policies;

use App\Infrastructure\Persistence\Eloquent\Models\ProjectMemberModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;

/**
 * spec/04_AUTHORIZATION_MATRIX.md: coarse RBAC (role on a project) plus
 * resource-scope enforcement (must be a member of *this* project).
 * "No controller may infer authorization from UI visibility. Policies
 * default deny when subject/resource/action is incomplete." - every method
 * here ends in an explicit boolean; there is no implicit allow path.
 */
final class ProjectPolicy
{
    private const ANY_ROLE = ['viewer', 'modeler', 'reviewer', 'approver', 'workspace_admin'];

    private const CAN_COMMIT_ROLES = ['modeler', 'workspace_admin'];

    public function view(?UserModel $user, string $projectId): bool
    {
        return $user !== null && $this->hasAnyRole($user, $projectId, self::ANY_ROLE);
    }

    public function commit(?UserModel $user, string $projectId): bool
    {
        return $user !== null && $this->hasAnyRole($user, $projectId, self::CAN_COMMIT_ROLES);
    }

    /** @param list<string> $roles */
    private function hasAnyRole(UserModel $user, string $projectId, array $roles): bool
    {
        return ProjectMemberModel::query()
            ->where('project_id', $projectId)
            ->where('user_id', $user->getKey())
            ->whereIn('role', $roles)
            ->exists();
    }
}

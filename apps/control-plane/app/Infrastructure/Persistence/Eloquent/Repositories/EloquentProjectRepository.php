<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Architecture\Project;
use App\Domain\Ports\ProjectRepositoryInterface;
use App\Domain\Shared\Uuid;
use App\Infrastructure\Persistence\Eloquent\Models\ProjectModel;

final class EloquentProjectRepository implements ProjectRepositoryInterface
{
    public function find(string $projectId): ?Project
    {
        if (! Uuid::isValid($projectId)) {
            return null;
        }

        /** @var ProjectModel|null $row */
        $row = ProjectModel::find($projectId);

        if ($row === null) {
            return null;
        }

        return new Project(
            id: $row->id,
            name: $row->name,
            headRevisionId: $row->head_revision_id,
            headVersion: $row->head_version,
        );
    }
}

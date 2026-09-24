<?php

declare(strict_types=1);

namespace App\Domain\Ports;

use App\Domain\Architecture\Project;

interface ProjectRepositoryInterface
{
    public function find(string $projectId): ?Project;
}

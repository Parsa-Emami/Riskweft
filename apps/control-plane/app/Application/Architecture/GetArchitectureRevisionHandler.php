<?php

declare(strict_types=1);

namespace App\Application\Architecture;

use App\Domain\Architecture\ArchitectureRevision;
use App\Domain\Architecture\Exceptions\ArchitectureRevisionNotFoundException;
use App\Domain\Ports\ArchitectureRevisionRepositoryInterface;

final class GetArchitectureRevisionHandler
{
    public function __construct(private readonly ArchitectureRevisionRepositoryInterface $revisions) {}

    public function handle(GetArchitectureRevisionQuery $query): ArchitectureRevision
    {
        $revision = $this->revisions->find($query->revisionId);

        if ($revision === null) {
            throw new ArchitectureRevisionNotFoundException($query->revisionId);
        }

        return $revision;
    }
}

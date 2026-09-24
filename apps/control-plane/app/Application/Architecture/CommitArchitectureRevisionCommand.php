<?php

declare(strict_types=1);

namespace App\Application\Architecture;

/**
 * DTO for POST /api/v1/projects/{project}/revisions
 * (contracts/openapi.v1.yaml riskweft_post_projects_project_revisions).
 * Built by the controller from the validated FormRequest; never constructed
 * from a raw Illuminate\Http\Request so the Application layer stays
 * decoupled from the HTTP transport.
 */
final class CommitArchitectureRevisionCommand
{
    /** @param array<string, mixed> $rawArchitecture Already JSON-decoded request body's "architecture" field. */
    public function __construct(
        public readonly string $projectId,
        public readonly ?string $baseRevisionId,
        public readonly array $rawArchitecture,
        public readonly ?string $actingUserId,
        public readonly string $correlationId,
    ) {}
}

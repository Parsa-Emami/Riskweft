<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Infrastructure\Persistence\Eloquent\Models\ProjectMemberModel;
use App\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Local/dev/CI convenience only - never exposed over HTTP. RiskWeft's
 * frozen OpenAPI contract (contracts/openapi.v1.yaml) has no login/token
 * endpoint: production authentication is an OIDC-token-verifying
 * infrastructure adapter in front of Sanctum's guard, which is out of
 * Phase 0's scope. This command exists so a developer (or a CI job, or the
 * Feature test suite) can get a bearer token and a project to call the API
 * against without that adapter existing yet.
 */
final class SeedDevActorCommand extends Command
{
    protected $signature = 'riskweft:dev:seed-actor
        {email : Email of the user to find or create}
        {project : Name of the project to find or create}
        {role=modeler : viewer|modeler|reviewer|approver|workspace_admin}';

    protected $description = 'Create/find a local dev user + project + membership and print a bearer token (dev/CI only).';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $projectName = (string) $this->argument('project');
        $role = (string) $this->argument('role');

        if (! in_array($role, ['viewer', 'modeler', 'reviewer', 'approver', 'workspace_admin'], true)) {
            $this->components->error("Unknown role [{$role}].");

            return self::FAILURE;
        }

        $user = UserModel::firstOrCreate(
            ['email' => $email],
            ['name' => Str::before($email, '@'), 'password' => Str::password(32)],
        );

        $project = ProjectModel::firstOrCreate(
            ['slug' => Str::slug($projectName)],
            ['name' => $projectName, 'head_version' => 0],
        );

        ProjectMemberModel::updateOrCreate(
            ['project_id' => $project->id, 'user_id' => $user->id],
            ['role' => $role],
        );

        $token = $user->createToken('dev-cli')->plainTextToken;

        $this->components->info('Dev actor ready.');
        $this->table(['field', 'value'], [
            ['user_id', $user->id],
            ['project_id', $project->id],
            ['role', $role],
            ['bearer_token', $token],
        ]);

        return self::SUCCESS;
    }
}

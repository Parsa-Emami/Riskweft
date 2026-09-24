<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Application\Architecture\CommitArchitectureRevisionCommand;
use App\Application\Architecture\CommitArchitectureRevisionHandler;
use App\Application\Architecture\GetArchitectureRevisionHandler;
use App\Application\Architecture\GetArchitectureRevisionQuery;
use App\Domain\Architecture\ArchitectureRevision;
use App\Domain\Architecture\Exceptions\ProjectNotFoundException;
use App\Domain\Ports\IdempotencyStoreInterface;
use App\Domain\Ports\ProjectRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\CommitArchitectureRevisionRequest;
use App\Http\Resources\ArchitectureRevisionResource;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Policies\ProjectPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * HTTP adapter for contracts/openapi.v1.yaml
 * riskweft_post_projects_project_revisions and riskweft_get_revisions_revision.
 * Contains no business logic of its own: it authenticates (via Sanctum
 * middleware, applied in routes/api.php), authorizes, translates HTTP <->
 * Application commands/queries, and shapes the OperationResult envelope.
 * The actual mutation happens in CommitArchitectureRevisionHandler
 * (spec/01_DOMAIN_CATALOG.md "never controller/model convenience writes").
 */
final class ArchitectureRevisionController extends Controller
{
    private const COMMIT_ROUTE = 'riskweft_post_projects_project_revisions';

    public function __construct(
        private readonly CommitArchitectureRevisionHandler $commitHandler,
        private readonly GetArchitectureRevisionHandler $getHandler,
        private readonly IdempotencyStoreInterface $idempotency,
        private readonly ProjectRepositoryInterface $projects,
        private readonly ProjectPolicy $projectPolicy,
    ) {}

    public function store(CommitArchitectureRevisionRequest $request, string $project): JsonResponse
    {
        $requestId = (string) $request->attributes->get('correlation_id');

        // Lookup before authorization (spec/04_AUTHORIZATION_MATRIX.md
        // "authorization is re-evaluated after lookup"): we cannot know
        // whether the caller may act on this project without first
        // confirming the project exists.
        if ($this->projects->find($project) === null) {
            throw new ProjectNotFoundException($project);
        }

        /** @var UserModel $user */
        $user = $request->user();

        if (! $this->projectPolicy->commit($user, $project)) {
            throw new AuthorizationException;
        }

        $validated = $request->validated();
        $idempotencyKey = (string) $request->header('Idempotency-Key');
        $fingerprint = hash('sha256', $project.'|'.json_encode($validated, JSON_THROW_ON_ERROR));

        $replay = $this->idempotency->reserveOrReplay($idempotencyKey, $user->getKey(), self::COMMIT_ROUTE, $fingerprint);
        if ($replay !== null) {
            return \App\Http\Support\ApiResponse::accepted($requestId, 'committed', $replay->resourceId, null, $replay->responseBody);
        }

        $baseRevisionId = $validated['base_revision_id'] === ArchitectureRevision::GENESIS_BASE_REVISION_ID
            ? null
            : $validated['base_revision_id'];

        $revision = $this->commitHandler->handle(new CommitArchitectureRevisionCommand(
            projectId: $project,
            baseRevisionId: $baseRevisionId,
            rawArchitecture: is_array($validated['architecture']) ? $validated['architecture'] : [],
            actingUserId: $user->getKey(),
            correlationId: $requestId,
        ));

        $data = (new ArchitectureRevisionResource($revision))->resolve();

        $this->idempotency->complete($idempotencyKey, $user->getKey(), self::COMMIT_ROUTE, 202, $data, $revision->id);

        return \App\Http\Support\ApiResponse::accepted($requestId, 'committed', $revision->id, $revision->sequenceNo, $data);
    }

    public function show(Request $request, string $revision): JsonResponse
    {
        $requestId = (string) $request->attributes->get('correlation_id');

        // Lookup first: we need the revision's project_id before we know
        // which project's membership to check.
        $result = $this->getHandler->handle(new GetArchitectureRevisionQuery($revision));

        /** @var UserModel $user */
        $user = $request->user();

        if (! $this->projectPolicy->view($user, $result->projectId)) {
            throw new AuthorizationException;
        }

        $data = (new ArchitectureRevisionResource($result))->resolve();

        return \App\Http\Support\ApiResponse::ok($requestId, 'ok', $result->id, $result->sequenceNo, $data);
    }
}

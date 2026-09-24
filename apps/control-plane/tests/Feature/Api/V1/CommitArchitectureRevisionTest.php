<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Infrastructure\Persistence\Eloquent\Models\OutboxMessageModel;
use App\Infrastructure\Persistence\Eloquent\Models\ProjectMemberModel;
use App\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end proof, through the real HTTP + auth + DB stack, of Phase 0's
 * "Required verification" items plus the ACCEPTANCE_GATES_V7.md global gate
 * "at least one negative/abuse/failure test exists for each
 * security-critical boundary added in the phase".
 */
final class CommitArchitectureRevisionTest extends TestCase
{
    use RefreshDatabase;

    private const ENTITY_A = '11111111-1111-4111-8111-111111111111';

    private const ENTITY_B = '22222222-2222-4222-8222-222222222222';

    public function test_a_modeler_can_commit_the_first_revision_of_a_project(): void
    {
        [$user, $project] = $this->actorWithRole('modeler');

        $response = $this->withHeaders($this->headers())
            ->postJson("/api/v1/projects/{$project->id}/revisions", $this->payload('genesis'));

        $response->assertStatus(202);
        $response->assertJsonPath('status', 'committed');
        $response->assertJsonPath('data.sequence_no', 1);
        $response->assertJsonPath('data.parent_revision_id', null);
        $response->assertJsonPath('data.entity_count', 2);
        $response->assertJsonPath('data.relationship_count', 1);
        self::assertIsString($response->json('data.content_hash'));
        self::assertStringStartsWith('sha256:', $response->json('data.content_hash'));

        $project->refresh();
        self::assertSame(1, $project->head_version);
        self::assertSame($response->json('data.id'), $project->head_revision_id);

        // Outbox row was written transactionally alongside the revision.
        self::assertSame(1, OutboxMessageModel::where('event_type', 'architecture.revision_committed.v1')
            ->where('aggregate_id', $response->json('data.id'))
            ->count());
    }

    public function test_committing_twice_from_genesis_without_reading_the_new_head_is_a_conflict_not_an_overwrite(): void
    {
        [$user, $project] = $this->actorWithRole('modeler');

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/projects/{$project->id}/revisions", $this->payload('genesis'))
            ->assertStatus(202);

        // A second client that still believes the project has no revisions
        // yet (spec/10_E2E_ACCEPTANCE_SCENARIOS.md scenario 4).
        $response = $this->withHeaders($this->headers())
            ->postJson("/api/v1/projects/{$project->id}/revisions", $this->payload('genesis'));

        $response->assertStatus(409);
        $response->assertJsonPath('code', 'REVISION_CONFLICT');
        $response->assertJsonPath('retryable', false);

        $project->refresh();
        self::assertSame(1, $project->head_version, 'the conflicting write must not have overwritten the head');
    }

    public function test_committing_against_the_current_head_succeeds_and_advances_the_sequence(): void
    {
        [$user, $project] = $this->actorWithRole('modeler');

        $first = $this->withHeaders($this->headers())
            ->postJson("/api/v1/projects/{$project->id}/revisions", $this->payload('genesis'));

        $second = $this->withHeaders($this->headers())
            ->postJson("/api/v1/projects/{$project->id}/revisions", $this->payload($first->json('data.id')));

        $second->assertStatus(202);
        $second->assertJsonPath('data.sequence_no', 2);
        $second->assertJsonPath('data.parent_revision_id', $first->json('data.id'));
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $project = ProjectModel::factory()->create();

        $response = $this->postJson("/api/v1/projects/{$project->id}/revisions", $this->payload('genesis'), [
            'Idempotency-Key' => str_repeat('a', 32),
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_a_user_with_no_membership_on_the_project_is_denied_cross_scope_access(): void
    {
        $stranger = UserModel::factory()->create();
        $project = ProjectModel::factory()->create();

        $response = $this->actingAs($stranger, 'sanctum')
            ->withHeaders($this->headers())
            ->postJson("/api/v1/projects/{$project->id}/revisions", $this->payload('genesis'));

        $response->assertStatus(403);
        $response->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_a_viewer_role_cannot_commit_a_revision(): void
    {
        [$viewer, $project] = $this->actorWithRole('viewer');

        $response = $this->withHeaders($this->headers())
            ->postJson("/api/v1/projects/{$project->id}/revisions", $this->payload('genesis'));

        $response->assertStatus(403);
    }

    public function test_a_nonexistent_project_returns_not_found_even_for_an_authenticated_user(): void
    {
        $user = UserModel::factory()->create();
        $missingProjectId = '99999999-9999-4999-8999-999999999999';

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders($this->headers())
            ->postJson("/api/v1/projects/{$missingProjectId}/revisions", $this->payload('genesis'));

        $response->assertStatus(404);
        $response->assertJsonPath('code', 'PROJECT_NOT_FOUND');
    }

    public function test_a_dangling_relationship_reference_is_rejected_with_a_validation_error(): void
    {
        [$user, $project] = $this->actorWithRole('modeler');

        $payload = [
            'base_revision_id' => 'genesis',
            'architecture' => [
                'metadata' => ['name' => 'Dangling'],
                'entities' => [
                    ['id' => self::ENTITY_A, 'kind' => 'component', 'name' => 'API'],
                ],
                'relationships' => [
                    ['id' => '33333333-3333-4333-8333-333333333333', 'kind' => 'data_flow', 'source_entity_id' => self::ENTITY_B, 'target_entity_id' => self::ENTITY_A],
                ],
            ],
        ];

        $response = $this->withHeaders($this->headers())
            ->postJson("/api/v1/projects/{$project->id}/revisions", $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('code', 'ARCHITECTURE_DOCUMENT_INVALID');
        self::assertNotEmpty($response->json('details.violations'));
    }

    public function test_missing_idempotency_key_is_rejected_before_any_commit_is_attempted(): void
    {
        [$user, $project] = $this->actorWithRole('modeler');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/projects/{$project->id}/revisions", $this->payload('genesis'));

        $response->assertStatus(422);
        $response->assertJsonPath('code', 'IDEMPOTENCY_KEY_REQUIRED');

        $project->refresh();
        self::assertSame(0, $project->head_version);
    }

    public function test_replaying_the_same_idempotency_key_and_body_returns_the_original_result_without_double_committing(): void
    {
        [$user, $project] = $this->actorWithRole('modeler');
        $headers = $this->headers('same-idempotency-key-123456789');

        $first = $this->withHeaders($headers)->postJson("/api/v1/projects/{$project->id}/revisions", $this->payload('genesis'));
        $second = $this->withHeaders($headers)->postJson("/api/v1/projects/{$project->id}/revisions", $this->payload('genesis'));

        $first->assertStatus(202);
        $second->assertStatus(202);
        self::assertSame($first->json('data.id'), $second->json('data.id'));

        $project->refresh();
        self::assertSame(1, $project->head_version, 'the replayed request must not have committed a second revision');
    }

    public function test_reusing_an_idempotency_key_with_a_different_body_is_rejected(): void
    {
        [$user, $project] = $this->actorWithRole('modeler');
        $headers = $this->headers('reused-key-but-different-body');

        $this->withHeaders($headers)->postJson("/api/v1/projects/{$project->id}/revisions", $this->payload('genesis'))
            ->assertStatus(202);

        $differentPayload = $this->payload('genesis');
        $differentPayload['architecture']['metadata']['name'] = 'A completely different architecture';

        $response = $this->withHeaders($headers)->postJson("/api/v1/projects/{$project->id}/revisions", $differentPayload);

        $response->assertStatus(409);
        $response->assertJsonPath('code', 'IDEMPOTENCY_KEY_CONFLICT');
    }

    /** @return array{0: UserModel, 1: ProjectModel} */
    private function actorWithRole(string $role): array
    {
        $user = UserModel::factory()->create();
        $project = ProjectModel::factory()->create();

        ProjectMemberModel::query()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        $this->actingAs($user, 'sanctum');

        return [$user, $project];
    }

    /** @return array<string, string> */
    private function headers(?string $idempotencyKey = null): array
    {
        return ['Idempotency-Key' => $idempotencyKey ?? bin2hex(random_bytes(16))];
    }

    /** @return array<string, mixed> */
    private function payload(string $baseRevisionId): array
    {
        return [
            'base_revision_id' => $baseRevisionId,
            'architecture' => [
                'metadata' => ['name' => 'Sample Architecture', 'description' => 'Two components, one flow.'],
                'entities' => [
                    ['id' => self::ENTITY_A, 'kind' => 'component', 'name' => 'API', 'attributes' => ['exposure' => 'internal']],
                    ['id' => self::ENTITY_B, 'kind' => 'asset', 'name' => 'Customer DB', 'attributes' => ['classification' => 'confidential']],
                ],
                'relationships' => [
                    [
                        'id' => '33333333-3333-4333-8333-333333333333',
                        'kind' => 'data_flow',
                        'source_entity_id' => self::ENTITY_A,
                        'target_entity_id' => self::ENTITY_B,
                        'label' => 'reads customer records',
                        'attributes' => ['protocol' => 'tls'],
                    ],
                ],
            ],
        ];
    }
}

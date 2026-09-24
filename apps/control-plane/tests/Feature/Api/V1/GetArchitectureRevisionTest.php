<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Infrastructure\Persistence\Eloquent\Models\ProjectMemberModel;
use App\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GetArchitectureRevisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_project_member_can_read_a_committed_revision_losslessly(): void
    {
        $user = UserModel::factory()->create();
        $project = ProjectModel::factory()->create();
        ProjectMemberModel::query()->create(['project_id' => $project->id, 'user_id' => $user->id, 'role' => 'viewer']);

        $entityId = '11111111-1111-4111-8111-111111111111';
        $commit = $this->actingAs($user, 'sanctum')
            ->withHeaders(['Idempotency-Key' => bin2hex(random_bytes(16))])
            ->postJson("/api/v1/projects/{$project->id}/revisions", [
                'base_revision_id' => 'genesis',
                'architecture' => [
                    'metadata' => ['name' => 'Round Trip'],
                    'entities' => [['id' => $entityId, 'kind' => 'component', 'name' => 'API']],
                    'relationships' => [],
                ],
            ]);
        $commit->assertStatus(202);
        $revisionId = $commit->json('data.id');
        $committedHash = $commit->json('data.content_hash');

        $read = $this->actingAs($user, 'sanctum')->getJson("/api/v1/revisions/{$revisionId}");

        $read->assertStatus(200);
        $read->assertJsonPath('data.id', $revisionId);
        $read->assertJsonPath('data.content_hash', $committedHash);
        $read->assertJsonPath('data.architecture.entities.0.id', $entityId);
        $read->assertJsonPath('data.architecture.metadata.name', 'Round Trip');
    }

    public function test_reading_an_unknown_revision_returns_not_found(): void
    {
        $user = UserModel::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/revisions/99999999-9999-4999-8999-999999999999');

        $response->assertStatus(404);
        $response->assertJsonPath('code', 'REVISION_NOT_FOUND');
    }

    public function test_a_non_member_cannot_read_a_revision_from_a_project_they_do_not_belong_to(): void
    {
        $owner = UserModel::factory()->create();
        $project = ProjectModel::factory()->create();
        ProjectMemberModel::query()->create(['project_id' => $project->id, 'user_id' => $owner->id, 'role' => 'modeler']);

        $commit = $this->actingAs($owner, 'sanctum')
            ->withHeaders(['Idempotency-Key' => bin2hex(random_bytes(16))])
            ->postJson("/api/v1/projects/{$project->id}/revisions", [
                'base_revision_id' => 'genesis',
                'architecture' => ['metadata' => ['name' => 'Private'], 'entities' => [], 'relationships' => []],
            ]);
        $revisionId = $commit->json('data.id');

        $stranger = UserModel::factory()->create();
        $response = $this->actingAs($stranger, 'sanctum')->getJson("/api/v1/revisions/{$revisionId}");

        $response->assertStatus(403);
        $response->assertJsonPath('code', 'FORBIDDEN');
    }
}

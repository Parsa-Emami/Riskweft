<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FIRST_30_COMMITS_V7.md item 4: "test: add clean-bootstrap smoke test".
 * /up is Laravel's built-in health route (declared via `health: '/up'` in
 * bootstrap/app.php) and is the one endpoint spec/04_AUTHORIZATION_MATRIX.md
 * explicitly allows to stay unauthenticated ("documented public health
 * endpoints").
 */
final class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_responds_without_authentication(): void
    {
        $this->get('/up')->assertStatus(200);
    }
}

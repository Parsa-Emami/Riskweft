<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * spec/07_OBSERVABILITY_CATALOG.md "Logging": "...no credentials, plaintext
 * secrets, raw tokens... or sensitive model rows" - the same bar applies to
 * error response bodies, since a response is just as visible as a log line.
 * ApiExceptionRenderer (registered for the whole app in bootstrap/app.php)
 * is the single choke point that must hold for every exception type, not
 * just the ones the rest of the Phase 0 suite happens to trigger - so this
 * test drives an arbitrary exception through a throwaway route rather than
 * relying on a real endpoint accidentally exercising the same code path.
 */
final class RedactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unexpected_exception_never_leaks_its_raw_message_or_a_stack_trace(): void
    {
        Route::middleware('api')->get('/api/v1/__test_only_throws', function () {
            throw new \RuntimeException('super-secret internal detail: db password is hunter2');
        });

        $response = $this->getJson('/api/v1/__test_only_throws');

        $response->assertStatus(500);
        $response->assertJsonPath('code', 'INTERNAL_ERROR');
        $body = $response->getContent();
        self::assertIsString($body);
        self::assertStringNotContainsString('hunter2', $body);
        self::assertStringNotContainsString('.php', $body);
    }

    public function test_error_responses_always_use_the_problem_json_content_type(): void
    {
        $response = $this->getJson('/api/v1/revisions/99999999-9999-4999-8999-999999999999');

        $response->assertStatus(401); // unauthenticated, since this test sends no bearer token
        self::assertStringContainsString('application/problem+json', (string) $response->headers->get('Content-Type'));
    }
}

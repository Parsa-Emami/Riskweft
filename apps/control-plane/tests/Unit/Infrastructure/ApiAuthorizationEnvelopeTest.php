<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class ApiAuthorizationEnvelopeTest extends TestCase
{
    public function test_framework_wrapped_policy_denials_preserve_the_public_error_contract(): void
    {
        Route::middleware('api')->get('/api/__authorization_probe', static function (): never {
            throw new AuthorizationException('synthetic-private-reason');
        });

        $response = $this->getJson('/api/__authorization_probe');
        $response->assertStatus(403)->assertJsonPath('code', 'FORBIDDEN');
        self::assertStringNotContainsString('synthetic-private-reason', (string) $response->getContent());
    }
}

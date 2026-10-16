<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class ApiRateLimitTest extends TestCase
{
    public function test_api_budget_is_enforced_per_principal_before_business_work(): void
    {
        Route::middleware('api')->get('/api/__rate_limit_probe', static fn () => response()->json(['ok' => true]));
        $first = UserModel::factory()->make();
        $first->id = '11111111-1111-4111-8111-111111111111';
        $this->actingAs($first, 'sanctum');

        for ($request = 0; $request < 120; $request++) {
            $this->getJson('/api/__rate_limit_probe')->assertOk();
        }
        $this->getJson('/api/__rate_limit_probe')->assertStatus(429)->assertJsonPath('code', 'RATE_LIMITED');

        $second = UserModel::factory()->make();
        $second->id = '22222222-2222-4222-8222-222222222222';
        $this->actingAs($second, 'sanctum');
        $this->getJson('/api/__rate_limit_probe')->assertOk();
    }
}

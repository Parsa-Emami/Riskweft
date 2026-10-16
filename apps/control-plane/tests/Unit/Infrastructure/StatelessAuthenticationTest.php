<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use Tests\TestCase;

final class StatelessAuthenticationTest extends TestCase
{
    public function test_missing_bearer_credentials_remain_guest_without_recursive_guard_fallback(): void
    {
        $this->assertGuest('web');
        $this->assertGuest('sanctum');
    }
}

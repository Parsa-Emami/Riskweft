<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use App\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Database\Factories\ProjectModelFactory;
use Database\Factories\UserModelFactory;
use Tests\TestCase;

final class FactoryDiscoveryTest extends TestCase
{
    public function test_nested_models_resolve_real_factories_and_build_models_without_database_writes(): void
    {
        $users = UserModel::factory();
        $projects = ProjectModel::factory();

        self::assertInstanceOf(UserModelFactory::class, $users);
        self::assertInstanceOf(ProjectModelFactory::class, $projects);
        self::assertInstanceOf(UserModel::class, $users->make());
        self::assertInstanceOf(ProjectModel::class, $projects->make());
    }
}

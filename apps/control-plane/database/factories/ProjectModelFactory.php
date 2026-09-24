<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ProjectModel> */
final class ProjectModelFactory extends Factory
{
    protected $model = ProjectModel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(6),
            'head_revision_id' => null,
            'head_version' => 0,
        ];
    }
}

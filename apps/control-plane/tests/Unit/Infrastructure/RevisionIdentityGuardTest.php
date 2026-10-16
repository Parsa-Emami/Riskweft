<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use App\Infrastructure\Persistence\Eloquent\Models\ArchitectureRevisionModel;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Tests\TestCase;

final class RevisionIdentityGuardTest extends TestCase
{
    public function test_revision_identity_cannot_be_replaced_through_mass_assignment(): void
    {
        $this->expectException(MassAssignmentException::class);

        new ArchitectureRevisionModel(['id' => '11111111-1111-4111-8111-111111111111']);
    }
}

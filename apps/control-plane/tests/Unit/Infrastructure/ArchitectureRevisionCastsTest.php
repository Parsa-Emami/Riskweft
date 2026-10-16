<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure;

use App\Domain\Architecture\RevisionStatus;
use App\Infrastructure\Persistence\Eloquent\Models\ArchitectureRevisionModel;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class ArchitectureRevisionCastsTest extends TestCase
{
    public function test_read_types_match_the_actual_model_casts_without_database_access(): void
    {
        $row = new ArchitectureRevisionModel;
        $row->setDateFormat('Y-m-d H:i:s');
        $row->setRawAttributes([
            'status' => RevisionStatus::Draft->value,
            'canonical_document' => '{"metadata":{"name":"Synthetic"},"entities":[],"relationships":[]}',
            'committed_at' => '2026-10-16 12:00:00',
        ]);

        self::assertSame(RevisionStatus::Draft, $row->status);
        self::assertSame('Synthetic', $row->canonical_document['metadata']['name']);
        self::assertInstanceOf(CarbonImmutable::class, $row->committed_at);
    }
}

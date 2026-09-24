<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\Architecture\EntityKind;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single node of a committed revision's canonical document, denormalized
 * into a typed, queryable row (Final Engine Decision #4: "Semantic diff
 * compares typed entities/relationships, not raw JSON text"). Written
 * transactionally alongside architecture_revisions.canonical_document by
 * EloquentArchitectureRevisionRepository - never independently.
 */
final class RevisionEntityModel extends Model
{
    use HasUuids;

    protected $table = 'revision_entities';

    public $timestamps = false;

    protected $fillable = ['revision_id', 'entity_id', 'kind', 'name', 'description', 'attributes'];

    protected function casts(): array
    {
        return [
            'kind' => EntityKind::class,
            'attributes' => 'array',
        ];
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(ArchitectureRevisionModel::class, 'revision_id');
    }
}

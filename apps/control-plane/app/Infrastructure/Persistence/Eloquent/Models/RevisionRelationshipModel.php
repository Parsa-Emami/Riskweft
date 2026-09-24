<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\Architecture\RelationshipKind;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RevisionRelationshipModel extends Model
{
    use HasUuids;

    protected $table = 'revision_relationships';

    public $timestamps = false;

    protected $fillable = [
        'revision_id', 'relationship_id', 'kind',
        'source_entity_row_id', 'target_entity_row_id', 'label', 'attributes',
    ];

    protected function casts(): array
    {
        return [
            'kind' => RelationshipKind::class,
            'attributes' => 'array',
        ];
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(ArchitectureRevisionModel::class, 'revision_id');
    }

    public function sourceEntity(): BelongsTo
    {
        return $this->belongsTo(RevisionEntityModel::class, 'source_entity_row_id');
    }

    public function targetEntity(): BelongsTo
    {
        return $this->belongsTo(RevisionEntityModel::class, 'target_entity_row_id');
    }
}

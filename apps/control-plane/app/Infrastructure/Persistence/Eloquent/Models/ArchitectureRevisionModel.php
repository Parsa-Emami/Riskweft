<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\Architecture\RevisionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Persistence shape for App\Domain\Architecture\ArchitectureRevision.
 *
 * No `updated_at`: an ArchitectureRevision is immutable once committed
 * (Frozen v1 Invariant #1), and the absence of an "updated at" column makes
 * that structurally visible rather than merely documented. A defense-in-depth
 * database trigger additionally rejects any UPDATE that touches the
 * content-bearing columns - see
 * database/migrations/..._add_immutability_guard_to_architecture_revisions.php.
 */
final class ArchitectureRevisionModel extends Model
{
    use HasUuids;

    protected $table = 'architecture_revisions';

    public $timestamps = false;

    protected $fillable = [
        'project_id', 'parent_revision_id', 'sequence_no', 'status',
        'canonical_document', 'content_hash', 'hash_algorithm',
        'entity_count', 'relationship_count', 'committed_by_user_id', 'committed_at',
    ];

    protected function casts(): array
    {
        return [
            'sequence_no' => 'integer',
            'status' => RevisionStatus::class,
            'canonical_document' => 'array',
            'entity_count' => 'integer',
            'relationship_count' => 'integer',
            'committed_at' => 'immutable_datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectModel::class, 'project_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_revision_id');
    }

    public function entities(): HasMany
    {
        return $this->hasMany(RevisionEntityModel::class, 'revision_id');
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(RevisionRelationshipModel::class, 'revision_id');
    }
}

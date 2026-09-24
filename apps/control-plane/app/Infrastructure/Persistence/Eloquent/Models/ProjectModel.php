<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProjectModel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'projects';

    protected $fillable = ['name', 'slug', 'head_revision_id', 'head_version', 'created_by_user_id'];

    protected function casts(): array
    {
        return [
            'head_version' => 'integer',
        ];
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ArchitectureRevisionModel::class, 'project_id');
    }

    public function headRevision(): BelongsTo
    {
        return $this->belongsTo(ArchitectureRevisionModel::class, 'head_revision_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMemberModel::class, 'project_id');
    }
}

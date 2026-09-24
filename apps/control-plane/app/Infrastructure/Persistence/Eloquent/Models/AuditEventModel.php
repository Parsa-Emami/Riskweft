<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class AuditEventModel extends Model
{
    use HasUuids;

    protected $table = 'audit_events';

    public $timestamps = false;

    protected $fillable = [
        'actor_user_id', 'action', 'resource_type', 'resource_id',
        'project_id', 'correlation_id', 'metadata', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }
}

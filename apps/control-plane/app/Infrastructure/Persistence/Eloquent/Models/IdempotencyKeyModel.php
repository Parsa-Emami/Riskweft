<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class IdempotencyKeyModel extends Model
{
    use HasUuids;

    protected $table = 'idempotency_keys';

    public $timestamps = false;

    protected $fillable = [
        'idempotency_key', 'principal_user_id', 'route', 'request_fingerprint',
        'response_status', 'response_body', 'resource_id', 'created_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'response_body' => 'array',
            'created_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }
}

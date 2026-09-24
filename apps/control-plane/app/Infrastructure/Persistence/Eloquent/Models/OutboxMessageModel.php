<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class OutboxMessageModel extends Model
{
    use HasUuids;

    protected $table = 'outbox_messages';

    public $timestamps = false;

    protected $fillable = [
        'aggregate_type', 'aggregate_id', 'event_type', 'event_id',
        'payload', 'correlation_id', 'causation_id', 'occurred_at', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
        ];
    }
}

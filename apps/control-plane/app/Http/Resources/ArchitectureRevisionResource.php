<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Architecture\ArchitectureRevision;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ArchitectureRevision $resource
 */
final class ArchitectureRevisionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $revision = $this->resource;

        return [
            'id' => $revision->id,
            'project_id' => $revision->projectId,
            'parent_revision_id' => $revision->parentRevisionId,
            'sequence_no' => $revision->sequenceNo,
            'status' => $revision->status->value,
            'content_hash' => $revision->contentHash->toString(),
            'entity_count' => $revision->document->entityCount(),
            'relationship_count' => $revision->document->relationshipCount(),
            'committed_by_user_id' => $revision->committedByUserId,
            'committed_at' => $revision->committedAt->format(DATE_ATOM),
            'architecture' => $revision->document->toCanonicalArray(),
        ];
    }
}

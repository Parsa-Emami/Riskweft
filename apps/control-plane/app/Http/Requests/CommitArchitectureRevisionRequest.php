<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates only the HTTP-level envelope shape from
 * contracts/openapi.v1.yaml riskweft_post_projects_project_revisions
 * requestBody. Everything about whether the *architecture* content itself
 * is a valid canonical document is the Domain layer's job
 * (App\Domain\Architecture\ArchitectureDocumentValidator) - this class does
 * not know what an entity or a relationship is.
 */
final class CommitArchitectureRevisionRequest extends FormRequest
{
    /** Authorization is re-evaluated after lookup in the controller/policy, not here (spec/04_AUTHORIZATION_MATRIX.md). */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'base_revision_id' => ['required', 'string', 'min:1', 'max:4096'],
            'architecture' => ['required', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        // OpenAPI declares additionalProperties: false on this request body.
        $validator->after(function (Validator $validator) {
            $unknown = array_diff(array_keys($this->input() ?? []), ['base_revision_id', 'architecture']);
            if ($unknown !== []) {
                $validator->errors()->add('*', 'Unknown field(s): '.implode(', ', $unknown).'.');
            }
        });
    }
}

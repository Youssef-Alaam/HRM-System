<?php

namespace App\Services;

use App\Models\DocumentType;
use App\Repositories\Contracts\DocumentTypeRepositoryInterface;

/**
 * Layer-3 service for the Document types matrix (Settings → Document
 * types, Admin only). Trusts validated input from the FormRequest.
 */
class DocumentTypeService extends BaseService
{
    public function __construct(
        private readonly DocumentTypeRepositoryInterface $repo,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): DocumentType
    {
        return $this->transaction(function () use ($data) {
            return $this->repo->create(array_merge([
                'org_id' => auth()->user()->org_id,
                'is_active' => true,
            ], $data));
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): DocumentType
    {
        return $this->transaction(function () use ($id, $data) {
            $type = $this->repo->findOrFail($id);

            return $this->repo->update($type, $data);
        });
    }

    public function disable(int $id): void
    {
        $this->transaction(function () use ($id) {
            $type = $this->repo->findOrFail($id);
            $this->repo->softDelete($type);
        });
    }
}

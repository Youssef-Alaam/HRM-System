<?php

namespace App\Repositories\Contracts;

use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface DocumentTypeRepositoryInterface
{
    public function find(int $id): ?DocumentType;

    public function findOrFail(int $id): DocumentType;

    /**
     * @return Collection<int, DocumentType>
     */
    public function activeForOrg(int $orgId): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): DocumentType;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Model $type, array $data): DocumentType;

    public function softDelete(DocumentType $type): bool;
}

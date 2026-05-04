<?php

namespace App\Repositories;

use App\Models\DocumentType;
use App\Repositories\Contracts\DocumentTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class DocumentTypeRepository extends BaseRepository implements DocumentTypeRepositoryInterface
{
    protected function model(): string
    {
        return DocumentType::class;
    }

    public function find(int $id): ?DocumentType
    {
        /** @var DocumentType|null */
        return parent::find($id);
    }

    public function findOrFail(int $id): DocumentType
    {
        /** @var DocumentType */
        return parent::findOrFail($id);
    }

    /**
     * @return Collection<int, DocumentType>
     */
    public function activeForOrg(int $orgId): Collection
    {
        /** @var Collection<int, DocumentType> */
        return $this->query()
            ->where('org_id', $orgId)
            ->where('is_active', true)
            ->orderBy('order_index')
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): DocumentType
    {
        /** @var DocumentType */
        return parent::create($data);
    }

    public function update(Model $type, array $data): DocumentType
    {
        /** @var DocumentType */
        return parent::update($type, $data);
    }

    public function softDelete(DocumentType $type): bool
    {
        // Disable rather than fully soft-delete so the active matrix
        // stops including it but historical employee_documents linked
        // to this type retain their reference.
        $type->is_active = false;
        $type->save();

        return (bool) $type->delete();
    }
}

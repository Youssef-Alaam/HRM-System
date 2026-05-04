<?php

namespace App\Repositories;

use App\Models\EmployeeDocument;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EmployeeDocumentRepository extends BaseRepository implements EmployeeDocumentRepositoryInterface
{
    protected function model(): string
    {
        return EmployeeDocument::class;
    }

    public function find(int $id): ?EmployeeDocument
    {
        /** @var EmployeeDocument|null */
        return parent::find($id);
    }

    public function findOrFail(int $id): EmployeeDocument
    {
        /** @var EmployeeDocument */
        return parent::findOrFail($id);
    }

    /**
     * @return Collection<int, EmployeeDocument>
     */
    public function forEmployee(int $employeeId): Collection
    {
        /** @var Collection<int, EmployeeDocument> */
        return $this->query()
            ->with('documentType', 'uploader')
            ->where('employee_id', $employeeId)
            ->orderBy('document_type_id')
            ->orderByDesc('uploaded_at')
            ->get();
    }

    public function findByEmployeeAndType(int $employeeId, ?int $documentTypeId): ?EmployeeDocument
    {
        /** @var EmployeeDocument|null */
        return $this->query()
            ->where('employee_id', $employeeId)
            ->where('document_type_id', $documentTypeId)
            ->first();
    }

    public function countActiveForEmployee(int $employeeId): int
    {
        return (int) $this->query()
            ->where('employee_id', $employeeId)
            ->count();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $q = $this->query()
            ->with(['employee:id,first_name,last_name,employee_code,is_expat', 'documentType:id,name,is_required']);

        if (! empty($filters['employee_id'])) {
            $q->where('employee_id', $filters['employee_id']);
        }
        if (! empty($filters['document_type_id'])) {
            $q->where('document_type_id', $filters['document_type_id']);
        }

        if (! empty($filters['expiring_within_days'])) {
            $now = CarbonImmutable::now();
            $cutoff = $now->addDays((int) $filters['expiring_within_days']);
            $q->whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [$now->toDateString(), $cutoff->toDateString()]);
        }

        if (! empty($filters['expired_only'])) {
            $q->whereNotNull('expiry_date')
                ->where('expiry_date', '<', CarbonImmutable::now()->toDateString());
        }

        return $q->orderByDesc('uploaded_at')->paginate($perPage);
    }

    public function create(array $data): EmployeeDocument
    {
        /** @var EmployeeDocument */
        return parent::create($data);
    }

    public function update(Model $document, array $data): EmployeeDocument
    {
        /** @var EmployeeDocument */
        return parent::update($document, $data);
    }

    public function softDelete(EmployeeDocument $document): bool
    {
        return (bool) $document->delete();
    }
}

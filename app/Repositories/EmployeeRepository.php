<?php

namespace App\Repositories;

use App\Models\Employee;
use App\Models\Position;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Support\PositionType;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class EmployeeRepository extends BaseRepository implements EmployeeRepositoryInterface
{
    protected function model(): string
    {
        return Employee::class;
    }

    public function find(int $id): ?Employee
    {
        /** @var Employee|null */
        return parent::find($id);
    }

    public function findOrFail(int $id): Employee
    {
        /** @var Employee */
        return parent::findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $perPage = 25, array $filters = []): LengthAwarePaginator
    {
        $q = $this->query()
            ->with(['department', 'position', 'office', 'manager']);

        if (! empty($filters['search'])) {
            $term = '%'.$filters['search'].'%';
            $q->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('employee_code', 'like', $term);
            });
        }

        foreach (['department_id', 'position_id', 'office_id', 'employment_status'] as $key) {
            if (! empty($filters[$key])) {
                $q->where($key, $filters[$key]);
            }
        }

        if (! empty($filters['manager_id'])) {
            $q->where('manager_id', $filters['manager_id']);
        }

        return $q->orderBy('last_name')->orderBy('first_name')->paginate($perPage);
    }

    public function create(array $data): Employee
    {
        /** @var Employee */
        return parent::create($data);
    }

    public function update(Model $employee, array $data): Employee
    {
        /** @var Employee */
        return parent::update($employee, $data);
    }

    public function softDelete(Employee $employee, string $reason): bool
    {
        // Reason rides the audit log via the Auditable trait — the controller
        // sets it on the model just before delete so the diff captures it.
        // Feature 17 (Compliance Workflows) will add a dedicated
        // termination_reason column + termination_date when the full flow lands.
        $employee->setAttribute('_delete_reason', $reason);

        return (bool) $employee->delete();
    }

    /**
     * Generate the next sticky employee_code in the EMP-XXXXX format
     * (locked 2026-04-30 with Walid). First digit = position type_code
     * (0-9); remaining four = tenure-ordered sequence within that bucket.
     *
     * Sticky: we pick the next number from the highest existing tenure
     * suffix INCLUDING soft-deleted rows, so vacated slots aren't reused.
     *
     * Concurrency: cheap counter. The unique (org_id, employee_code)
     * index will reject a duplicate write so the caller can retry.
     */
    public function generateEmployeeCode(int $orgId, int $positionId): string
    {
        $position = Position::query()
            ->withTrashed()
            ->where('id', $positionId)
            ->where('org_id', $orgId)
            ->firstOrFail();

        $typeCode = (int) ($position->type_code ?? PositionType::OTHER);
        $prefix = sprintf('EMP-%d', $typeCode);

        $highest = $this->query()
            ->withTrashed()
            ->where('org_id', $orgId)
            ->where('employee_code', 'like', $prefix.'%')
            ->pluck('employee_code')
            ->map(fn (string $code) => (int) substr($code, strlen($prefix)))
            ->max() ?? 0;

        $next = $highest + 1;

        if ($next > 9999) {
            throw new DomainException(
                "EMP-{$typeCode} bucket has overflowed 9999 entries — widen tenure padding before issuing another code."
            );
        }

        return sprintf('EMP-%d%04d', $typeCode, $next);
    }
}

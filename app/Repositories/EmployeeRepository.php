<?php

namespace App\Repositories;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
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
     * Generate the next sequential employee_code for the org.
     * Format: YZH-{org_id}-{4-digit sequence}.
     * Cheap counter; if multiple HRs create concurrently the unique index
     * on (org_id, employee_code) will reject and the caller retries.
     */
    public function generateEmployeeCode(int $orgId): string
    {
        $lastNumber = $this->query()
            ->withTrashed()
            ->where('org_id', $orgId)
            ->where('employee_code', 'like', "YZH-{$orgId}-%")
            ->get()
            ->map(fn ($e) => (int) substr($e->employee_code, strrpos($e->employee_code, '-') + 1))
            ->max() ?? 0;

        return sprintf('YZH-%d-%04d', $orgId, $lastNumber + 1);
    }
}

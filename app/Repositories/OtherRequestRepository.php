<?php

namespace App\Repositories;

use App\Models\Employee;
use App\Models\OtherRequest;
use App\Repositories\Contracts\OtherRequestRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OtherRequestRepository extends BaseRepository implements OtherRequestRepositoryInterface
{
    protected function model(): string
    {
        return OtherRequest::class;
    }

    public function forEmployee(int $orgId, int $employeeId, ?string $status): LengthAwarePaginator
    {
        return OtherRequest::query()
            ->where('org_id', $orgId)
            ->where('employee_id', $employeeId)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with('approvedBy:id,name', 'rejectedBy:id,name')
            ->orderByDesc('request_date')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();
    }

    public function pendingForApprover(int $orgId, int $approvingUserId, bool $isFinalApprover): LengthAwarePaginator
    {
        $query = OtherRequest::query()
            ->where('org_id', $orgId)
            ->where('status', 'pending')
            ->with('employee:id,first_name,last_name,department_id', 'employee.department:id,name');

        if (! $isFinalApprover) {
            // Manager sees direct reports only
            $managedIds = Employee::query()
                ->join('users', 'users.employee_id', '=', 'employees.id')
                ->where('users.id', $approvingUserId)
                ->value('employees.id');

            if (! $managedIds) {
                $directReportIds = [];
            } else {
                $directReportIds = Employee::query()
                    ->where('manager_id', $managedIds)
                    ->pluck('id')
                    ->all();
            }

            $query->whereIn('employee_id', $directReportIds);
        }

        return $query->orderByDesc('created_at')->paginate(25)->withQueryString();
    }

    public function findOrFail(int $id): OtherRequest
    {
        /** @var OtherRequest */
        return OtherRequest::query()
            ->with('employee:id,first_name,last_name,org_id,manager_id', 'approvedBy:id,name', 'rejectedBy:id,name')
            ->findOrFail($id);
    }

    public function create(array $data): OtherRequest
    {
        /** @var OtherRequest */
        return OtherRequest::create($data);
    }

    public function approve(OtherRequest $request, int $approverUserId): OtherRequest
    {
        $request->update([
            'status' => 'approved',
            'approved_by_user_id' => $approverUserId,
            'approved_at' => now(),
        ]);

        return $request->refresh();
    }

    public function reject(OtherRequest $request, int $rejectorUserId, string $reason): OtherRequest
    {
        $request->update([
            'status' => 'rejected',
            'rejected_by_user_id' => $rejectorUserId,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $request->refresh();
    }
}

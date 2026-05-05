<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use App\Repositories\Contracts\LeaveTypeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ApprovalService extends BaseService
{
    public function __construct(
        private readonly LeaveRequestRepositoryInterface $leaveRequests,
        private readonly LeaveTypeRepositoryInterface $leaveTypes,
        private readonly LeaveService $leaveService,
    ) {}

    public function pendingForApprover(User $user): LengthAwarePaginator
    {
        if ($user->can('leave.approve.final')) {
            return $this->leaveRequests->paginateForHr((int) $user->org_id);
        }

        $empId = $user->employee_id ? (int) $user->employee_id : 0;

        return $this->leaveRequests->paginateForManager($empId, (int) $user->org_id);
    }

    public function approve(User $approver, int $requestId): LeaveRequest
    {
        $req = $this->leaveRequests->find($requestId);

        if (! $req || (int) $req->org_id !== (int) $approver->org_id) {
            abort(404);
        }
        if ($req->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Only pending requests can be approved.']);
        }

        $this->assertCanActOn($approver, $req);

        return $this->transaction(function () use ($approver, $req) {
            $updated = $this->leaveRequests->updateRequest($req, [
                'status' => 'approved',
                'approved_by_user_id' => $approver->id,
                'approved_at' => now(),
            ]);

            $employee = Employee::find($req->employee_id);
            $leaveType = $this->leaveTypes->find((int) $req->leave_type_id);

            if ($employee && $leaveType) {
                $this->leaveService->adjustBalance($employee, $leaveType, -(int) $req->days_count);
            }

            return $updated;
        });
    }

    public function reject(User $approver, int $requestId, string $reason): LeaveRequest
    {
        $req = $this->leaveRequests->find($requestId);

        if (! $req || (int) $req->org_id !== (int) $approver->org_id) {
            abort(404);
        }
        if ($req->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Only pending requests can be rejected.']);
        }

        $this->assertCanActOn($approver, $req);

        // Per Decision 19: rights-based leave cannot be rejected by team managers
        if (! $approver->can('leave.approve.final')) {
            $leaveType = $this->leaveTypes->find((int) $req->leave_type_id);
            if ($leaveType && $leaveType->is_right_not_discretion) {
                throw ValidationException::withMessages([
                    'rejected_reason' => 'Rights-based leave cannot be rejected by team managers. Escalate to HR.',
                ]);
            }
        }

        return $this->transaction(function () use ($req, $reason) {
            return $this->leaveRequests->updateRequest($req, [
                'status' => 'rejected',
                'rejected_reason' => $reason,
            ]);
        });
    }

    private function assertCanActOn(User $approver, LeaveRequest $req): void
    {
        if ($approver->can('leave.approve.final')) {
            return;
        }

        // Manager: verify the request belongs to a direct report
        $managerEmpId = $approver->employee_id ? (int) $approver->employee_id : 0;
        $employee = Employee::withTrashed()->find($req->employee_id);

        if (! $employee || (int) $employee->manager_id !== $managerEmpId) {
            abort(403);
        }
    }
}

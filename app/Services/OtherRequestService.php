<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\OtherRequest;
use App\Models\User;
use App\Repositories\Contracts\OtherRequestRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class OtherRequestService extends BaseService
{
    public function __construct(private readonly OtherRequestRepositoryInterface $repo) {}

    public function myRequests(int $orgId, int $employeeId, ?string $status): LengthAwarePaginator
    {
        return $this->repo->forEmployee($orgId, $employeeId, $status);
    }

    public function pendingForApprover(User $user): LengthAwarePaginator
    {
        $isFinal = $user->can('requests.approve.final');

        return $this->repo->pendingForApprover(
            (int) $user->org_id,
            $user->id,
            $isFinal,
        );
    }

    public function submit(int $orgId, User $user, array $data): OtherRequest
    {
        $employee = Employee::findOrFail($user->employee_id);

        // Per Labor Law Art. 85 — overtime capped at 2 hrs/day
        if (($data['type'] ?? '') === 'overtime') {
            $hours = (float) ($data['hours_requested'] ?? 0);
            if ($hours > 2) {
                throw ValidationException::withMessages([
                    'hours_requested' => 'Per Labor Law Art. 85, overtime is capped at 2 hours per day.',
                ]);
            }
        }

        $autoApprove = $employee->manager_id === null;

        return $this->transaction(function () use ($orgId, $employee, $user, $data, $autoApprove) {
            $payload = array_merge($data, [
                'org_id' => $orgId,
                'employee_id' => $employee->id,
                'status' => $autoApprove ? 'approved' : 'pending',
            ]);

            if ($autoApprove) {
                $payload['approved_by_user_id'] = $user->id;
                $payload['approved_at'] = now();
            }

            return $this->repo->create($payload);
        });
    }

    public function approve(User $approver, int $requestId): OtherRequest
    {
        return $this->transaction(function () use ($approver, $requestId) {
            $req = $this->repo->findOrFail($requestId);

            abort_unless((int) $req->org_id === (int) $approver->org_id, 403);
            $this->assertCanActOn($approver, $req);

            return $this->repo->approve($req, $approver->id);
        });
    }

    public function reject(User $approver, int $requestId, string $reason): OtherRequest
    {
        return $this->transaction(function () use ($approver, $requestId, $reason) {
            $req = $this->repo->findOrFail($requestId);

            abort_unless((int) $req->org_id === (int) $approver->org_id, 403);
            $this->assertCanActOn($approver, $req);

            return $this->repo->reject($req, $approver->id, $reason);
        });
    }

    private function assertCanActOn(User $approver, OtherRequest $req): void
    {
        if ($approver->can('requests.approve.final')) {
            return;
        }

        $managerEmp = Employee::withTrashed()
            ->join('users', 'users.employee_id', '=', 'employees.id')
            ->where('users.id', $approver->id)
            ->select('employees.id')
            ->first();

        $managerEmpId = $managerEmp?->id ?? 0;
        $employee = Employee::withTrashed()->find($req->employee_id);

        abort_unless(
            $employee && (int) $employee->manager_id === $managerEmpId,
            403,
        );
    }
}

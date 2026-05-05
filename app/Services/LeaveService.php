<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use App\Repositories\Contracts\LeaveTypeRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class LeaveService extends BaseService
{
    public function __construct(
        private readonly LeaveTypeRepositoryInterface $leaveTypes,
        private readonly LeaveRequestRepositoryInterface $leaveRequests,
    ) {}

    public function activeTypes(): Collection
    {
        return $this->leaveTypes->allActive();
    }

    public function paginateForEmployee(int $employeeId, string $tab): LengthAwarePaginator
    {
        return $this->leaveRequests->paginateForEmployee($employeeId, $tab);
    }

    /** @param array<string, mixed> $data */
    public function create(User $user, array $data, ?object $attachment): LeaveRequest
    {
        $employee = Employee::findOrFail($user->employee_id);
        $leaveType = $this->leaveTypes->find((int) $data['leave_type_id']);
        $count = $this->countWorkdays($data['start_date'], $data['end_date'], (int) $user->org_id);
        $path = $attachment ? $attachment->store('leave-attachments', 'local') : null;

        return $this->transaction(function () use ($user, $employee, $leaveType, $data, $count, $path) {
            // Per Decision 13: NULL manager_id auto-approves own requests, HR notified via audit log
            $autoApprove = $employee->manager_id === null;

            $req = $this->leaveRequests->create([
                'org_id' => $user->org_id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'days_count' => $count,
                'status' => $autoApprove ? 'approved' : 'pending',
                'reason' => $data['reason'] ?? null,
                'attachment_path' => $path,
                'approved_by_user_id' => null,
                'approved_at' => $autoApprove ? now() : null,
            ]);

            if ($autoApprove) {
                $this->adjustBalance($employee, $leaveType, -$count);
            }

            return $req;
        });
    }

    public function cancelOwn(User $user, int $requestId): LeaveRequest
    {
        $req = $this->leaveRequests->find($requestId);

        if (! $req || $req->employee_id !== $user->employee_id) {
            abort(403);
        }
        if ($req->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Only pending requests can be cancelled.']);
        }

        return $this->transaction(function () use ($req) {
            return $this->leaveRequests->updateRequest($req, ['status' => 'cancelled', 'cancelled_at' => now()]);
        });
    }

    public function cancelWithOverride(int $requestId): LeaveRequest
    {
        $req = $this->leaveRequests->find($requestId);
        if (! $req) {
            abort(404);
        }

        return $this->transaction(function () use ($req) {
            if ($req->status === 'approved') {
                $emp = Employee::find($req->employee_id);
                $type = $this->leaveTypes->find($req->leave_type_id);
                if ($emp && $type) {
                    $this->adjustBalance($emp, $type, $req->days_count);
                }
            }
            $updated = $this->leaveRequests->updateRequest($req, ['status' => 'cancelled', 'cancelled_at' => now()]);
            $updated->delete();

            return $updated;
        });
    }

    /**
     * Count workdays (excludes Fri+Sat and org holidays).
     * Per Egyptian Labor Law: Friday and Saturday are the standard weekend days.
     */
    public function countWorkdays(string $start, string $end, int $orgId): int
    {
        $holidays = Holiday::query()
            ->where('org_id', $orgId)
            ->whereBetween('date', [$start, $end])
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
            ->flip()->all();

        $count = 0;
        $current = Carbon::parse($start);
        $endDate = Carbon::parse($end);

        while ($current->lte($endDate)) {
            $dow = $current->dayOfWeek;
            if ($dow !== Carbon::FRIDAY && $dow !== Carbon::SATURDAY && ! isset($holidays[$current->format('Y-m-d')])) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    public function adjustBalance(Employee $employee, LeaveType $type, int $delta): void
    {
        $field = match ($type->code) {
            'annual' => 'annual_leave_balance_days',
            'sick' => 'sick_leave_balance_days',
            'casual' => 'casual_leave_balance_days',
            default => null,
        };
        if ($field) {
            if ($delta < 0) {
                $employee->decrement($field, abs($delta));
            } else {
                $employee->increment($field, $delta);
            }
        }
    }
}

<?php

namespace App\Repositories;

use App\Models\LeaveRequest;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LeaveRequestRepository extends BaseRepository implements LeaveRequestRepositoryInterface
{
    protected function model(): string
    {
        return LeaveRequest::class;
    }

    public function paginateForEmployee(int $employeeId, string $status): LengthAwarePaginator
    {
        return LeaveRequest::query()
            ->where('employee_id', $employeeId)
            ->where('status', $status)
            ->with('leaveType')
            ->latest('created_at')
            ->paginate(20);
    }

    public function find(int $id): ?LeaveRequest
    {
        return LeaveRequest::query()->find($id);
    }

    public function create(array $data): LeaveRequest
    {
        return LeaveRequest::create($data);
    }

    public function updateRequest(LeaveRequest $request, array $data): LeaveRequest
    {
        $request->update($data);

        return $request->fresh();
    }

    public function hasOverlap(int $employeeId, string $start, string $end, ?int $excludeId = null): bool
    {
        return LeaveRequest::query()
            ->where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'approved'])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where(fn ($q) => $q
                ->whereBetween('start_date', [$start, $end])
                ->orWhereBetween('end_date', [$start, $end])
                ->orWhere(fn ($q2) => $q2
                    ->where('start_date', '<=', $start)
                    ->where('end_date', '>=', $end)
                )
            )
            ->exists();
    }

    public function usedDays(int $employeeId, int $leaveTypeId): int
    {
        // Only count pending requests; approved are already deducted from the employee balance column
        return (int) LeaveRequest::query()
            ->where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('status', 'pending')
            ->sum('days_count');
    }
}

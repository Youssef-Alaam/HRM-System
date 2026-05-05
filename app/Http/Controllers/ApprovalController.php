<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApproveLeaveRequest;
use App\Http\Requests\RejectLeaveRequest;
use App\Models\LeaveRequest;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalController extends Controller
{
    public function __construct(private readonly ApprovalService $approvals) {}

    public function index(): Response
    {
        $user = auth()->user();
        $paginated = $this->approvals->pendingForApprover($user);

        $requests = $paginated->through(fn ($req) => [
            'id' => $req->id,
            'start_date' => $req->start_date->format('Y-m-d'),
            'end_date' => $req->end_date->format('Y-m-d'),
            'days_count' => $req->days_count,
            'status' => $req->status,
            'reason' => $req->reason,
            'leave_type' => $req->leaveType ? [
                'id' => $req->leaveType->id,
                'code' => $req->leaveType->code,
                'name' => $req->leaveType->name,
                'is_right_not_discretion' => $req->leaveType->is_right_not_discretion,
            ] : null,
            'employee' => $req->employee ? [
                'id' => $req->employee->id,
                'name' => $req->employee->first_name.' '.$req->employee->last_name,
                'department' => $req->employee->department?->name,
            ] : null,
        ]);

        return Inertia::render('Approvals/Index', [
            'requests' => $requests,
            'role' => $user->can('leave.approve.final') ? 'hr' : 'manager',
        ]);
    }

    public function approve(ApproveLeaveRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->approvals->approve(auth()->user(), $leaveRequest->id);

        return back()->with('success', 'Leave request approved.');
    }

    public function reject(RejectLeaveRequest $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->approvals->reject(auth()->user(), $leaveRequest->id, $request->validated()['rejected_reason']);

        return back()->with('success', 'Leave request rejected.');
    }
}

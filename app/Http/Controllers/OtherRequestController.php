<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApproveOtherRequestRequest;
use App\Http\Requests\RejectOtherRequestRequest;
use App\Http\Requests\StoreOtherRequestRequest;
use App\Services\OtherRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OtherRequestController extends Controller
{
    public function __construct(private readonly OtherRequestService $service) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('requests.create.own'), 403);

        $user = $request->user();
        $tab = $request->query('tab', 'mine');
        $status = $request->query('status');

        $employeeId = (int) $user->employee_id;
        $orgId = (int) $user->org_id;

        $mine = $this->service->myRequests($orgId, $employeeId, $status ?: null);

        $pending = ($tab === 'pending' && ($user->can('requests.approve.team') || $user->can('requests.approve.final')))
            ? $this->service->pendingForApprover($user)
            : null;

        return Inertia::render('Requests/Index', [
            'mine' => $mine->through(fn ($r) => $this->serialize($r)),
            'pending' => $pending?->through(fn ($r) => $this->serializePending($r)),
            'tab' => $tab,
            'can_approve' => $user->can('requests.approve.team') || $user->can('requests.approve.final'),
            'has_employee' => $employeeId > 0,
        ]);
    }

    public function store(StoreOtherRequestRequest $request): RedirectResponse
    {
        $this->service->submit(
            (int) $request->user()->org_id,
            $request->user(),
            $request->validated(),
        );

        return back()->with('status', 'Request submitted.');
    }

    public function approve(int $otherRequest, ApproveOtherRequestRequest $request): RedirectResponse
    {
        $this->service->approve($request->user(), $otherRequest);

        return back()->with('status', 'Request approved.');
    }

    public function reject(int $otherRequest, RejectOtherRequestRequest $request): RedirectResponse
    {
        $this->service->reject(
            $request->user(),
            $otherRequest,
            $request->validated()['rejection_reason'],
        );

        return back()->with('status', 'Request rejected.');
    }

    /** @return array<string, mixed> */
    private function serialize($r): array
    {
        return [
            'id' => $r->id,
            'type' => $r->type,
            'status' => $r->status,
            'request_date' => $r->request_date->format('Y-m-d'),
            'hours_requested' => $r->hours_requested,
            'amount_piasters' => $r->amount_piasters,
            'notes' => $r->notes,
            'rejection_reason' => $r->rejection_reason,
            'approved_by' => $r->approvedBy ? ['name' => $r->approvedBy->name] : null,
            'rejected_by' => $r->rejectedBy ? ['name' => $r->rejectedBy->name] : null,
            'approved_at' => $r->approved_at?->toIso8601String(),
            'created_at' => $r->created_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function serializePending($r): array
    {
        return array_merge($this->serialize($r), [
            'employee' => $r->employee ? [
                'id' => $r->employee->id,
                'name' => $r->employee->first_name.' '.$r->employee->last_name,
                'department' => $r->employee->department?->name,
            ] : null,
        ]);
    }
}

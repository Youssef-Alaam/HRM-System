<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeaveRequest;
use App\Services\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveController extends Controller
{
    public function __construct(private readonly LeaveService $service) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $tab = in_array($request->query('tab'), ['pending', 'approved', 'rejected'], true)
            ? $request->query('tab')
            : 'pending';

        $employeeId = $user->employee_id;
        $requests = $employeeId
            ? $this->service->paginateForEmployee($employeeId, $tab)
            : collect()->paginate(20);

        return Inertia::render('Leave/Index', [
            'tab' => $tab,
            'requests' => $requests,
            'leaveTypes' => $this->service->activeTypes(),
        ]);
    }

    public function store(StoreLeaveRequest $request): RedirectResponse
    {
        $this->service->create(
            $request->user(),
            $request->validated(),
            $request->file('attachment'),
        );

        return back()->with('success', 'Leave request submitted.');
    }

    public function cancel(Request $request, int $leaveRequest): RedirectResponse
    {
        $this->service->cancelOwn($request->user(), $leaveRequest);

        return back()->with('success', 'Leave request cancelled.');
    }

    public function cancelWithOverride(Request $request, int $leaveRequest): RedirectResponse
    {
        $this->service->cancelWithOverride($leaveRequest);

        return back()->with('success', 'Leave request cancelled (override).');
    }
}

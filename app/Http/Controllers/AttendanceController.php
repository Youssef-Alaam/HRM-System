<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckInRequest;
use App\Http\Requests\CheckOutRequest;
use App\Models\Employee;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $service) {}

    /** Current user's enrolled face descriptor — needed by the browser check-in widget. */
    public function myDescriptor(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('attendance.checkin.own'), 403);

        if (! $request->user()->employee_id) {
            return response()->json(['descriptor' => null, 'enrolled' => false]);
        }

        $employee = Employee::find($request->user()->employee_id);
        abort_unless($employee && (int) $employee->org_id === (int) $request->user()->org_id, 403);

        return response()->json([
            'descriptor' => $employee->face_descriptor,
            'enrolled' => $employee->face_descriptor !== null,
        ]);
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('attendance.view.own'), 403);

        $user = $request->user();
        $orgId = (int) $user->org_id;
        $employeeId = (int) $user->employee_id;

        if (! $employeeId) {
            return Inertia::render('Attendance/Index', [
                'records' => null,
                'has_employee' => false,
            ]);
        }

        $cairoDate = Carbon::now('Africa/Cairo')->toDateString();
        $lastToday = app(AttendanceRepositoryInterface::class)
            ->lastEventForEmployeeToday($orgId, $employeeId, $cairoDate);
        $currentMode = ($lastToday && $lastToday->type === 'check_in') ? 'check_out' : 'check_in';

        return Inertia::render('Attendance/Index', [
            'records' => app(AttendanceRepositoryInterface::class)
                ->paginateForEmployee($orgId, $employeeId)
                ->through(fn ($r) => $this->serialize($r)),
            'has_employee' => true,
            'current_mode' => $currentMode,
            'face_enrolled' => Employee::find($employeeId)?->face_descriptor !== null,
        ]);
    }

    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $employee = Employee::findOrFail($request->user()->employee_id);
        abort_unless((int) $employee->org_id === (int) $request->user()->org_id, 403);

        $record = $this->service->checkIn($employee, $request->validated());

        return response()->json([
            'status' => 'ok',
            'record' => $this->serialize($record),
        ], 201);
    }

    public function checkOut(CheckOutRequest $request): JsonResponse
    {
        $employee = Employee::findOrFail($request->user()->employee_id);
        abort_unless((int) $employee->org_id === (int) $request->user()->org_id, 403);

        $record = $this->service->checkOut($employee, $request->validated());

        return response()->json([
            'status' => 'ok',
            'record' => $this->serialize($record),
        ], 201);
    }

    /** Admin/HR view of all records with filters. */
    public function adminIndex(Request $request): Response
    {
        abort_unless($request->user()->can('attendance.view.any'), 403);

        $orgId = (int) $request->user()->org_id;
        $records = app(AttendanceRepositoryInterface::class)
            ->paginateForOrg($orgId, [
                'employee_id' => $request->query('employee_id'),
                'from' => $request->query('from'),
                'to' => $request->query('to'),
                'verdict' => $request->query('verdict'),
            ]);

        return Inertia::render('Attendance/Admin', [
            'records' => $records->through(fn ($r) => $this->serializeAdmin($r)),
            'filters' => $request->only(['employee_id', 'from', 'to', 'verdict']),
        ]);
    }

    public function correct(int $record, Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('attendance.edit.any'), 403);

        $validated = $request->validate([
            'event_at' => ['nullable', 'date'],
            'is_late' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->service->correct($record, (int) $request->user()->id, $validated);

        return back()->with('status', 'Attendance record corrected.');
    }

    /** @return array<string, mixed> */
    private function serialize($r): array
    {
        return [
            'id' => $r->id,
            'type' => $r->type,
            'event_at' => $r->event_at?->toIso8601String(),
            'event_date' => $r->event_date?->toDateString(),
            'is_late' => (bool) $r->is_late,
            'office' => $r->office ? ['id' => $r->office->id, 'name' => $r->office->name] : null,
            'verdict' => $r->verdict,
            'verdict_score' => $r->verdict_score,
            'distance_meters' => $r->distance_meters,
            'corrected_at' => $r->corrected_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function serializeAdmin($r): array
    {
        return array_merge($this->serialize($r), [
            'employee' => $r->employee ? [
                'id' => $r->employee->id,
                'name' => $r->employee->first_name.' '.$r->employee->last_name,
            ] : null,
        ]);
    }
}

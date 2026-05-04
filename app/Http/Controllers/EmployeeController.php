<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Asset;
use App\Models\Department;
use App\Models\DocumentType;
use App\Models\Office;
use App\Models\Position;
use App\Repositories\Contracts\AssetRepositoryInterface;
use App\Repositories\Contracts\DocumentTypeRepositoryInterface;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeDocumentService;
use App\Services\EmployeeService;
use App\Services\FaceEnrollmentService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Feature 2 — Employees CRUD.
 *
 * index/show render Inertia pages for browser navigation; explicit JSON
 * requests (Accept: application/json) return the raw data shape so Tinker
 * and future API consumers don't go through the Inertia layer.
 */
class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService $service,
        private readonly EmployeeRepositoryInterface $repo,
    ) {}

    public function index(Request $request)
    {
        abort_unless(
            $request->user()?->can('employees.view.team') || $request->user()?->can('employees.view.any'),
            403,
        );

        $filters = $request->only(['search', 'department_id', 'position_id', 'office_id', 'employment_status']);

        // Manager scope: only own team. HR/Admin: whole org (OrgScope handles).
        if ($request->user()->can('employees.view.any') === false
            && $request->user()->can('employees.view.team')) {
            $filters['manager_id'] = $request->user()->employee_id;
        }

        $employees = $this->repo->paginate(25, $filters);

        if ($request->wantsJson()) {
            return response()->json(['data' => $employees]);
        }

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'filters' => $filters,
            'canCreate' => $request->user()->can('employees.create'),
        ]);
    }

    public function show(
        int $employee,
        Request $request,
        DocumentTypeRepositoryInterface $documentTypes,
        EmployeeDocumentRepositoryInterface $documents,
        AssetRepositoryInterface $assets,
    ) {
        $row = $this->repo->findOrFail($employee);

        // Self-view always allowed; otherwise need team or any.
        $isSelf = $request->user()->employee_id === $row->id;
        $canViewAny = $request->user()->can('employees.view.any');
        $canViewTeam = $request->user()->can('employees.view.team') && $row->manager_id === $request->user()->employee_id;

        if (! $isSelf && ! $canViewAny && ! $canViewTeam) {
            abort(403);
        }

        $row->load(['department', 'position', 'office', 'manager']);

        if ($request->wantsJson()) {
            return response()->json(['data' => $row]);
        }

        // Documents matrix is HR/Admin only — Walid 2026-04-30: HR holds
        // the records, employees and managers don't get self-service.
        $canViewDocuments = $request->user()->can('documents.view.any');
        $documentsPayload = null;
        if ($canViewDocuments) {
            $types = $documentTypes->activeForOrg((int) $row->org_id);
            $matrix = $types->map(fn (DocumentType $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'name_ar' => $t->name_ar,
                'applies_to' => $t->applies_to,
                'is_required' => (bool) $t->is_required,
                'applies_to_employee' => $t->appliesTo($row),
                'default_expiry_months' => $t->default_expiry_months,
            ])->values();

            $uploaded = $documents->forEmployee($row->id);
            $documentsPayload = [
                'matrix' => $matrix->all(),
                'uploaded' => $uploaded->map(fn ($d) => [
                    'id' => $d->id,
                    'document_type_id' => $d->document_type_id,
                    'original_filename' => $d->original_filename,
                    'mime_type' => $d->mime_type,
                    'file_size_bytes' => (int) $d->file_size_bytes,
                    'issued_date' => optional($d->issued_date)->toDateString(),
                    'expiry_date' => optional($d->expiry_date)->toDateString(),
                    'is_expired' => $d->isExpired(),
                    'uploaded_at' => $d->uploaded_at?->toIso8601String(),
                    'uploaded_by' => $d->uploader?->name,
                    'notes' => $d->notes,
                ])->values()->all(),
                'cap' => EmployeeDocumentService::PER_EMPLOYEE_CAP,
            ];
        }

        // Face enrollment status is exposed to HR/Admin only — they
        // launch the wizard from this page; everyone else just sees
        // their own check-in flow under Feature 5.
        $canViewFace = $request->user()->can('face.view.any');
        $canEnrollFace = $request->user()->can('face.enroll.any');
        $canResetFace = $request->user()->can('face.reset');
        $facePayload = null;
        if ($canViewFace) {
            $hasDescriptor = $row->face_descriptor !== null;
            $lastEnrolledAt = $row->last_face_enrollment_at;
            $monthsSince = $lastEnrolledAt
                ? (int) $lastEnrolledAt->diffInMonths(CarbonImmutable::now())
                : null;
            $facePayload = [
                'has_descriptor' => $hasDescriptor,
                'last_enrolled_at' => optional($lastEnrolledAt)->toIso8601String(),
                'months_since' => $monthsSince,
                'cadence_months' => FaceEnrollmentService::REENROLLMENT_INTERVAL_MONTHS,
                'failed_checkin_count' => (int) $row->failed_checkin_count,
                'requires_reenrollment' => (bool) $row->requires_face_reenrollment,
                'min_quality_score' => FaceEnrollmentService::MIN_QUALITY_SCORE,
                'photo_count' => FaceEnrollmentService::PHOTO_COUNT,
            ];
        }

        // Assets section is HR/Admin only — same rationale as Documents.
        // Employees view their own assets via the /assets sidebar item.
        $canViewAssets = $request->user()->can('assets.view.any');
        $assetsPayload = null;
        if ($canViewAssets) {
            $assigned = $assets->currentForEmployee($row->id);
            $assetsPayload = [
                'current' => $assigned->map(fn (Asset $a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'serial_number' => $a->serial_number,
                    'value_piasters' => (int) $a->value_piasters,
                    'current_status' => $a->current_status,
                    'category' => $a->category
                        ? ['id' => $a->category->id, 'name' => $a->category->name]
                        : null,
                ])->values()->all(),
            ];
        }

        return Inertia::render('Employees/Show', [
            'employee' => $row,
            'canDelete' => $request->user()->can('employees.delete'),
            'canManageDocuments' => $request->user()->can('documents.upload'),
            'documents' => $documentsPayload,
            'assets' => $assetsPayload,
            'face' => $facePayload,
            'canEnrollFace' => $canEnrollFace,
            'canResetFace' => $canResetFace,
        ]);
    }

    public function create(Request $request)
    {
        // FormRequest middleware on the route gates access; we still need
        // the org-scoped picker data for the form.
        $orgId = $request->user()->org_id;

        $departments = Department::query()
            ->where('org_id', $orgId)
            ->orderBy('name')
            ->get(['id', 'name']);
        $positions = Position::query()
            ->where('org_id', $orgId)
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'department_id']);
        $offices = Office::query()
            ->where('org_id', $orgId)
            ->orderBy('name')
            ->get(['id', 'name']);
        $managers = $this->repo->query()
            ->whereNull('deleted_at')
            ->where('employment_status', 'active')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn ($e) => [
                'id' => $e->id,
                'name' => trim($e->first_name.' '.$e->last_name),
            ]);

        return Inertia::render('Employees/Create', [
            'departments' => $departments,
            'positions' => $positions,
            'offices' => $offices,
            'managers' => $managers,
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = $this->service->create($request->validated());

        return redirect()->route('employees.show', $employee->id)
            ->with('status', 'Employee created.');
    }

    public function update(int $employee, UpdateEmployeeRequest $request): RedirectResponse
    {
        // The FormRequest's authorize() has already enforced tier rules
        // (Self-tier on self, HR-only requires employees.edit.any).
        $this->service->update($employee, $request->validated());

        // Profile-page self-edits should land back on /profile; HR/Admin
        // edits land on the employee's detail page.
        $isSelf = (int) $request->user()->employee_id === $employee;
        $route = $isSelf && ! $request->user()->can('employees.edit.any')
            ? '/profile'
            : route('employees.show', $employee);

        return redirect($route)->with('status', 'Profile updated.');
    }

    public function destroy(int $employee, Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('employees.delete'), 403);

        $reason = (string) $request->input('reason', '');
        if (trim($reason) === '') {
            return back()->withErrors(['reason' => 'A reason is required to soft-delete an employee.']);
        }

        $this->service->softDelete($employee, $reason);

        return redirect()->route('employees.index')
            ->with('status', 'Employee soft-deleted.');
    }
}

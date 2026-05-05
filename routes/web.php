<?php

use App\Http\Controllers\Admin\AssetCategoryController as AdminAssetCategoryController;
use App\Http\Controllers\Admin\DocumentTypeController as AdminDocumentTypeController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentsController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FaceEnrollmentController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\OrgChartController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

$placeholder = fn (string $title, string $description): Closure => fn () => Inertia::render('Placeholder', [
    'title' => $title,
    'description' => $description,
]);

Route::middleware(['auth'])->group(function () use ($placeholder) {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Foundation-phase placeholders (F7)
    |--------------------------------------------------------------------------
    | Each sidebar item points here. The placeholder page renders the same
    | shell with title + description so the navigation and active-state
    | highlighting can be exercised before the Feature Phase starts.
    | Permission middleware is wired to the granular catalog from F4.
    */

    // People — Feature 2 Employees CRUD (replaces placeholder).
    // Permission middleware on the resource group; controller does finer-grained scope.
    Route::get('/employees', [EmployeeController::class, 'index'])
        ->middleware('permission:employees.view.team|employees.view.any')
        ->name('employees.index');
    Route::get('/employees/create', [EmployeeController::class, 'create'])
        ->middleware('permission:employees.create')
        ->name('employees.create');
    Route::post('/employees', [EmployeeController::class, 'store'])
        ->middleware('permission:employees.create')
        ->name('employees.store');
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])
        ->whereNumber('employee')
        ->name('employees.show');
    Route::patch('/employees/{employee}', [EmployeeController::class, 'update'])
        ->whereNumber('employee')
        ->name('employees.update');
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])
        ->whereNumber('employee')
        ->middleware('permission:employees.delete')
        ->name('employees.destroy');
    // Org chart — visible to any authenticated user (employees, managers,
    // HR, admin). Data scoping happens server-side per Decision 13.
    Route::get('/org-chart', [OrgChartController::class, 'index'])
        ->name('org-chart.index');
    // Documents — HR + Admin only (Feature 11). Sidebar landing lists
    // every employee's compliance state; per-employee uploads live under
    // /employees/{id}/documents.
    Route::get('/documents', [DocumentsController::class, 'index'])
        ->middleware('permission:documents.view.any')
        ->name('documents.index');
    Route::post('/employees/{employee}/documents', [EmployeeDocumentController::class, 'store'])
        ->whereNumber('employee')
        ->middleware('permission:documents.upload')
        ->name('employees.documents.store');
    Route::patch('/employees/{employee}/documents/{document}', [EmployeeDocumentController::class, 'update'])
        ->whereNumber('employee')->whereNumber('document')
        ->middleware('permission:documents.upload')
        ->name('employees.documents.update');
    Route::delete('/employees/{employee}/documents/{document}', [EmployeeDocumentController::class, 'destroy'])
        ->whereNumber('employee')->whereNumber('document')
        ->name('employees.documents.destroy');
    Route::get('/employees/{employee}/documents/{document}/download', [EmployeeDocumentController::class, 'download'])
        ->whereNumber('employee')->whereNumber('document')
        ->middleware('permission:documents.view.any')
        ->name('employees.documents.download');

    // Face enrollment (per face-enrollment.md spec) — HR + Admin only.
    Route::post('/employees/{employee}/face-enrollment', [FaceEnrollmentController::class, 'store'])
        ->whereNumber('employee')
        ->middleware('permission:face.enroll.any')
        ->name('employees.face-enrollment.store');
    Route::post('/employees/{employee}/face-enrollment/reset', [FaceEnrollmentController::class, 'reset'])
        ->whereNumber('employee')
        ->middleware('permission:face.reset')
        ->name('employees.face-enrollment.reset');
    // Assets — Feature 22 (Phase 1 promotion). Sidebar item visible to
    // every authenticated user; the controller scopes the response per
    // role (employees + managers see own current only; HR + Admin see all).
    Route::get('/assets', [AssetController::class, 'index'])
        ->name('assets.index');
    Route::get('/assets/create', [AssetController::class, 'create'])
        ->middleware('permission:assets.create')
        ->name('assets.create');
    Route::post('/assets', [AssetController::class, 'store'])
        ->middleware('permission:assets.create')
        ->name('assets.store');
    Route::get('/assets/{asset}', [AssetController::class, 'show'])
        ->whereNumber('asset')
        ->middleware('permission:assets.view.any')
        ->name('assets.show');
    Route::patch('/assets/{asset}', [AssetController::class, 'update'])
        ->whereNumber('asset')
        ->middleware('permission:assets.create')
        ->name('assets.update');
    Route::post('/assets/{asset}/assign', [AssetController::class, 'assign'])
        ->whereNumber('asset')
        ->middleware('permission:assets.assign')
        ->name('assets.assign');
    Route::post('/assets/{asset}/return', [AssetController::class, 'return'])
        ->whereNumber('asset')
        ->middleware('permission:assets.assign')
        ->name('assets.return');
    Route::post('/assets/{asset}/mark-lost', [AssetController::class, 'markLost'])
        ->whereNumber('asset')
        ->middleware('permission:assets.assign')
        ->name('assets.mark-lost');
    Route::post('/assets/{asset}/mark-damaged', [AssetController::class, 'markDamaged'])
        ->whereNumber('asset')
        ->middleware('permission:assets.assign')
        ->name('assets.mark-damaged');
    Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])
        ->whereNumber('asset')
        ->middleware('permission:assets.delete')
        ->name('assets.destroy');

    /*
    |--------------------------------------------------------------------------
    | Settings (admin) — moved out of the sidebar into the top-right dropdown
    |--------------------------------------------------------------------------
    | Departments / Positions / Offices / Holidays were sidebar items in F7;
    | locked 2026-04-30 to live under Settings. Their existing permissions
    | are the org.* manage gates (HR + Admin only).
    */
    Route::get('/departments', $placeholder('Departments', 'Department hierarchy and reporting structure.'))
        ->middleware('permission:org.departments.manage')->name('placeholder.departments');
    Route::get('/positions', $placeholder('Positions', 'Job titles and levels per department.'))
        ->middleware('permission:org.positions.manage')->name('placeholder.positions');
    Route::get('/offices', $placeholder('Offices', 'Physical locations with GPS coordinates and check-in radius.'))
        ->middleware('permission:org.offices.manage')->name('placeholder.offices');

    // Time
    Route::get('/attendance', $placeholder('Attendance', 'GPS + selfie check-in records with face verification.'))
        ->middleware('permission:attendance.view.own')->name('placeholder.attendance');
    Route::get('/schedule', [ScheduleController::class, 'index'])
        ->middleware('permission:attendance.view.own')
        ->name('schedule.index');
    Route::get('/holidays', $placeholder('Holiday calendar', 'Egyptian public holidays and make-up day rules.'))
        ->middleware('permission:org.holidays.manage')->name('placeholder.holidays');

    // Leave — Feature 6
    Route::get('/my-leave', [LeaveController::class, 'index'])
        ->middleware('permission:leave.view.own')
        ->name('leave.index');
    Route::post('/my-leave', [LeaveController::class, 'store'])
        ->middleware('permission:leave.request.own')
        ->name('leave.store');
    Route::post('/my-leave/{leaveRequest}/cancel', [LeaveController::class, 'cancel'])
        ->whereNumber('leaveRequest')
        ->middleware('permission:leave.request.own')
        ->name('leave.cancel');
    Route::post('/my-leave/{leaveRequest}/cancel-with-override', [LeaveController::class, 'cancelWithOverride'])
        ->whereNumber('leaveRequest')
        ->middleware('permission:leave.edit.any')
        ->name('leave.cancel-override');
    Route::get('/leave/balances', $placeholder('Leave Balances', 'Annual, sick, casual, emergency credit, and comp-day balances.'))
        ->middleware('permission:leave.view.own')->name('placeholder.leave-balances');

    // Approvals — Feature 7 (manager + HR queue)
    Route::middleware('permission:leave.approve.team|leave.approve.final')->group(function () {
        Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
        Route::post('/approvals/{leaveRequest}/approve', [ApprovalController::class, 'approve'])
            ->whereNumber('leaveRequest')->name('approvals.approve');
        Route::post('/approvals/{leaveRequest}/reject', [ApprovalController::class, 'reject'])
            ->whereNumber('leaveRequest')->name('approvals.reject');
    });

    // Requests
    Route::get('/requests', $placeholder('My Requests', 'Cert letters, document requests, attendance corrections.'))
        ->middleware('permission:requests.create.own')->name('placeholder.requests');

    // Payroll
    Route::get('/payroll', $placeholder('Payroll Runs', 'Run, lock, and mark payroll cycles as paid.'))
        ->middleware('permission:payroll.run')->name('placeholder.payroll');
    Route::get('/payslips', $placeholder('Payslips', 'Your payslip history and downloads.'))
        ->middleware('permission:payroll.payslip.view.own')->name('placeholder.payslips');

    // Reports
    Route::get('/reports', $placeholder('Reports', 'Headcount, leave usage, attendance trends, payroll summaries.'))
        ->middleware('permission:reports.run.own')->name('placeholder.reports');

    // Communication
    Route::get('/messages', $placeholder('Messages', 'Direct chat with teammates.'))
        ->middleware('permission:chat.send')->name('placeholder.messages');
    Route::get('/announcements', $placeholder('Announcements', 'Company-wide and department-targeted broadcasts.'))
        ->middleware('permission:announcements.view')->name('placeholder.announcements');

    // Settings (admin)
    Route::middleware('permission:settings.document_types.manage')
        ->prefix('admin/document-types')
        ->name('admin.document-types.')
        ->group(function () {
            Route::get('/', [AdminDocumentTypeController::class, 'index'])->name('index');
            Route::post('/', [AdminDocumentTypeController::class, 'store'])->name('store');
            Route::patch('/{type}', [AdminDocumentTypeController::class, 'update'])
                ->whereNumber('type')->name('update');
            Route::delete('/{type}', [AdminDocumentTypeController::class, 'destroy'])
                ->whereNumber('type')->name('destroy');
        });
    Route::middleware('permission:settings.asset_categories.manage')
        ->prefix('admin/asset-categories')
        ->name('admin.asset-categories.')
        ->group(function () {
            Route::get('/', [AdminAssetCategoryController::class, 'index'])->name('index');
            Route::post('/', [AdminAssetCategoryController::class, 'store'])->name('store');
            Route::patch('/{category}', [AdminAssetCategoryController::class, 'update'])
                ->whereNumber('category')->name('update');
            Route::delete('/{category}', [AdminAssetCategoryController::class, 'destroy'])
                ->whereNumber('category')->name('destroy');
        });
    /*
    |--------------------------------------------------------------------------
    | Exports (queue item 9, locked 2026-04-30)
    |--------------------------------------------------------------------------
    | CSV + XLSX downloads for the three list surfaces. Gated on
    | exports.any (HR + Admin) — tighter than the read permissions on
    | purpose: bulk extract is a higher-trust action than browsing.
    */
    Route::middleware('permission:exports.any')->prefix('exports')->name('exports.')->group(function () {
        Route::get('/employees', [ExportController::class, 'employees'])->name('employees');
        Route::get('/assets', [ExportController::class, 'assets'])->name('assets');
        Route::get('/documents', [ExportController::class, 'documents'])->name('documents');
    });

    Route::get('/admin/users', $placeholder('Users & Roles', 'Create accounts, assign roles, toggle per-user permission overrides.'))
        ->middleware('permission:users.assign_roles')->name('placeholder.admin.users');
    Route::get('/admin/audit-log', $placeholder('Audit Log', 'Immutable trail of every write across the system.'))
        ->middleware('permission:audit.view')->name('placeholder.admin.audit-log');
    Route::get('/admin/settings', $placeholder('System Settings', 'Org-wide configuration: compliance values, integrations, branding.'))
        ->middleware('permission:settings.edit')->name('placeholder.admin.settings');

    /*
    |--------------------------------------------------------------------------
    | F9 reference vertical: Holiday CRUD (JSON only)
    |--------------------------------------------------------------------------
    | Exercises the full Controller → FormRequest → Service → Repository →
    | Model pipeline for the layered-architecture scaffolding (F9). No nav
    | item points here — the sidebar still hits /holidays (placeholder).
    | Feature 9 (Settings → Holiday Calendar) will swap these handlers to
    | Inertia::render() and add the admin pages.
    */
    Route::middleware('permission:org.holidays.manage')
        ->prefix('admin/holidays')
        ->name('admin.holidays.')
        ->group(function () {
            Route::get('/', [HolidayController::class, 'index'])->name('index');
            Route::post('/', [HolidayController::class, 'store'])->name('store');
            Route::get('/{holiday}', [HolidayController::class, 'show'])
                ->whereNumber('holiday')->name('show');
            Route::patch('/{holiday}', [HolidayController::class, 'update'])
                ->whereNumber('holiday')->name('update');
            Route::delete('/{holiday}', [HolidayController::class, 'destroy'])
                ->whereNumber('holiday')->name('destroy');
        });
});

require __DIR__.'/auth.php';

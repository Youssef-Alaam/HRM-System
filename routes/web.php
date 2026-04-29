<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
    ]);
});

$placeholder = fn (string $title, string $description): Closure => fn () => Inertia::render('Placeholder', [
    'title' => $title,
    'description' => $description,
]);

Route::middleware(['auth'])->group(function () use ($placeholder) {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

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

    // People
    Route::get('/employees', $placeholder('Employees', 'Roster of all employees, profiles, contracts, expat documents.'))
        ->middleware('permission:employees.view.own')->name('placeholder.employees');
    Route::get('/departments', $placeholder('Departments', 'Department hierarchy and reporting structure.'))
        ->middleware('permission:employees.view.own')->name('placeholder.departments');
    Route::get('/positions', $placeholder('Positions', 'Job titles and levels per department.'))
        ->middleware('permission:employees.view.own')->name('placeholder.positions');
    Route::get('/offices', $placeholder('Offices', 'Physical locations with GPS coordinates and check-in radius.'))
        ->middleware('permission:employees.view.own')->name('placeholder.offices');

    // Time
    Route::get('/attendance', $placeholder('Attendance', 'GPS + selfie check-in records with face verification.'))
        ->middleware('permission:attendance.view.own')->name('placeholder.attendance');
    Route::get('/schedules', $placeholder('Schedules', 'Shift assignments per employee and team.'))
        ->middleware('permission:attendance.view.own')->name('placeholder.schedules');
    Route::get('/holidays', $placeholder('Holidays', 'Egyptian public holidays and make-up day rules.'))
        ->middleware('permission:org.holidays.manage')->name('placeholder.holidays');

    // Leave
    Route::get('/leave', $placeholder('Leave Requests', 'Request leave; approvals routed via your manager and HR.'))
        ->middleware('permission:leave.request.own')->name('placeholder.leave');
    Route::get('/leave/balances', $placeholder('Leave Balances', 'Annual, sick, casual, emergency credit, and comp-day balances.'))
        ->middleware('permission:leave.view.own')->name('placeholder.leave-balances');

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
    Route::get('/admin/users', $placeholder('Users & Roles', 'Create accounts, assign roles, toggle per-user permission overrides.'))
        ->middleware('permission:users.assign_roles')->name('placeholder.admin.users');
    Route::get('/admin/audit-log', $placeholder('Audit Log', 'Immutable trail of every write across the system.'))
        ->middleware('permission:audit.view')->name('placeholder.admin.audit-log');
    Route::get('/admin/settings', $placeholder('System Settings', 'Org-wide configuration: compliance values, integrations, branding.'))
        ->middleware('permission:settings.edit')->name('placeholder.admin.settings');
});

require __DIR__.'/auth.php';

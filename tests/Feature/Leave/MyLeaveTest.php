<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Office;
use App\Models\Organization;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $this->org    = Organization::factory()->create();
    $this->office = Office::factory()->create(['org_id' => $this->org->id]);
    $this->dept   = Department::factory()->create(['org_id' => $this->org->id]);
    $this->pos    = Position::factory()->create([
        'org_id'        => $this->org->id,
        'department_id' => $this->dept->id,
    ]);

    $this->user = User::factory()->create(['org_id' => $this->org->id]);
    $this->user->assignRole('employee');

    $this->employee = Employee::factory()->create([
        'org_id'           => $this->org->id,
        'office_id'        => $this->office->id,
        'department_id'    => $this->dept->id,
        'position_id'      => $this->pos->id,
        'employment_status' => 'active',
        'gender'           => 'male',
        'annual_leave_balance_days' => 21,
        'sick_leave_balance_days'   => 30,
        'casual_leave_balance_days' => 7,
        'manager_id'       => null,
    ]);
    $this->user->update(['employee_id' => $this->employee->id]);

    // Create a manager employee to use as a manager
    $this->managerUser = User::factory()->create(['org_id' => $this->org->id]);
    $this->managerUser->assignRole('manager');
    $this->managerEmp = Employee::factory()->create([
        'org_id'          => $this->org->id,
        'office_id'       => $this->office->id,
        'department_id'   => $this->dept->id,
        'position_id'     => $this->pos->id,
        'employment_status' => 'active',
        'manager_id'      => null,
    ]);
    $this->managerUser->update(['employee_id' => $this->managerEmp->id]);

    // Default leave types
    $this->annualType = LeaveType::factory()->create([
        'org_id'                 => $this->org->id,
        'code'                   => 'annual',
        'name'                   => 'Annual Leave',
        'default_balance_days'   => 21,
        'is_right_not_discretion' => false,
        'applies_to'             => 'all',
        'is_active'              => true,
    ]);
    $this->sickType = LeaveType::factory()->create([
        'org_id'                          => $this->org->id,
        'code'                            => 'sick',
        'name'                            => 'Sick Leave',
        'default_balance_days'            => 90,
        'requires_certificate_after_days' => 3,
        'is_right_not_discretion'         => true,
        'applies_to'                      => 'all',
        'is_active'                       => true,
    ]);
    $this->studyType = LeaveType::factory()->create([
        'org_id'                 => $this->org->id,
        'code'                   => 'study',
        'name'                   => 'Study Leave',
        'default_balance_days'   => 10,
        'advance_notice_days'    => 10,
        'is_right_not_discretion' => false,
        'applies_to'             => 'all',
        'is_active'              => true,
    ]);
    $this->maternityType = LeaveType::factory()->create([
        'org_id'                 => $this->org->id,
        'code'                   => 'maternity',
        'name'                   => 'Maternity Leave',
        'default_balance_days'   => 120,
        'is_right_not_discretion' => true,
        'applies_to'             => 'female',
        'is_active'              => true,
    ]);
});

// Helper: valid annual leave payload
function annualLeavePayload(array $override = []): array
{
    return array_merge([
        'leave_type_id' => test()->annualType->id,
        'start_date'    => now()->addDays(3)->format('Y-m-d'),
        'end_date'      => now()->addDays(4)->format('Y-m-d'),  // ~2 workdays
        'reason'        => 'Family event',
    ], $override);
}

// ─── Auth ────────────────────────────────────────────────────────────────────

describe('my-leave — auth', function () {
    test('it redirects guests to login', function () {
        $this->get('/my-leave')->assertRedirect('/login');
    });

    test('it renders the page for an authenticated employee', function () {
        $this->actingAs($this->user)
            ->get('/my-leave')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Leave/Index'));
    });
});

// ─── Page rendering ───────────────────────────────────────────────────────────

describe('my-leave — page data', function () {
    test('it returns pending requests tab by default', function () {
        $this->actingAs($this->user)
            ->get('/my-leave')
            ->assertInertia(fn ($p) => $p
                ->where('tab', 'pending')
                ->has('requests')
                ->has('leaveTypes')
            );
    });

    test('it filters by approved tab', function () {
        LeaveRequest::factory()->create([
            'org_id'        => $this->org->id,
            'employee_id'   => $this->employee->id,
            'leave_type_id' => $this->annualType->id,
            'status'        => 'approved',
            'start_date'    => now()->addDays(10)->format('Y-m-d'),
            'end_date'      => now()->addDays(11)->format('Y-m-d'),
            'days_count'    => 2,
        ]);
        LeaveRequest::factory()->create([
            'org_id'        => $this->org->id,
            'employee_id'   => $this->employee->id,
            'leave_type_id' => $this->annualType->id,
            'status'        => 'pending',
            'start_date'    => now()->addDays(20)->format('Y-m-d'),
            'end_date'      => now()->addDays(21)->format('Y-m-d'),
            'days_count'    => 2,
        ]);

        $this->actingAs($this->user)
            ->get('/my-leave?tab=approved')
            ->assertInertia(fn ($p) => $p
                ->where('tab', 'approved')
                ->has('requests.data', 1)
            );
    });
});

// ─── Store (happy path) ───────────────────────────────────────────────────────

describe('my-leave — store happy path', function () {
    test('it creates a pending leave request', function () {
        // Give the employee a manager so it stays pending
        $this->employee->update(['manager_id' => $this->managerEmp->id]);

        $this->actingAs($this->user)
            ->post('/my-leave', annualLeavePayload())
            ->assertRedirect();

        expect(LeaveRequest::where('employee_id', $this->employee->id)->count())->toBe(1);
        expect(LeaveRequest::where('employee_id', $this->employee->id)->first()->status)->toBe('pending');
    });

    test('it auto-approves when employee has no manager (Decision 13)', function () {
        // employee has manager_id = null (set in beforeEach)
        $this->actingAs($this->user)
            ->post('/my-leave', annualLeavePayload())
            ->assertRedirect();

        $req = LeaveRequest::where('employee_id', $this->employee->id)->first();
        expect($req->status)->toBe('approved');
        expect($req->approved_at)->not->toBeNull();
    });

    test('it decrements the leave balance on auto-approval', function () {
        $before  = (float) $this->employee->annual_leave_balance_days;
        // Use a future Sunday+2 days → 3 workdays (Sun/Mon/Tue)
        $nextSun = \Carbon\Carbon::now()->addWeeks(4)->startOfWeek(\Carbon\Carbon::SUNDAY);
        $start   = $nextSun->format('Y-m-d');
        $end     = $nextSun->copy()->addDays(2)->format('Y-m-d');

        $this->actingAs($this->user)
            ->post('/my-leave', annualLeavePayload(['start_date' => $start, 'end_date' => $end]))
            ->assertRedirect();

        $this->employee->refresh();
        expect((float) $this->employee->annual_leave_balance_days)->toBe($before - 3.0);
    });
});

// ─── Validation ───────────────────────────────────────────────────────────────

describe('my-leave — validation', function () {
    test('it rejects start_date in the past', function () {
        $this->actingAs($this->user)
            ->post('/my-leave', annualLeavePayload([
                'start_date' => now()->subDay()->format('Y-m-d'),
                'end_date'   => now()->format('Y-m-d'),
            ]))
            ->assertSessionHasErrors('start_date');
    });

    test('it rejects end_date before start_date', function () {
        $this->actingAs($this->user)
            ->post('/my-leave', annualLeavePayload([
                'start_date' => now()->addDays(5)->format('Y-m-d'),
                'end_date'   => now()->addDays(3)->format('Y-m-d'),
            ]))
            ->assertSessionHasErrors('end_date');
    });

    test('it rejects a request that exceeds the balance', function () {
        $this->employee->update(['annual_leave_balance_days' => 1]);

        // Future Sun-Thu = 5 workdays (exceeds balance of 1)
        $nextSun = \Carbon\Carbon::now()->addWeeks(4)->startOfWeek(\Carbon\Carbon::SUNDAY);
        $start   = $nextSun->format('Y-m-d');
        $end     = $nextSun->copy()->addDays(4)->format('Y-m-d');

        $this->actingAs($this->user)
            ->post('/my-leave', annualLeavePayload(['start_date' => $start, 'end_date' => $end]))
            ->assertSessionHasErrors('days_count');
    });

    test('it rejects overlapping requests', function () {
        $this->employee->update(['manager_id' => $this->managerEmp->id]);
        LeaveRequest::factory()->create([
            'org_id'        => $this->org->id,
            'employee_id'   => $this->employee->id,
            'leave_type_id' => $this->annualType->id,
            'status'        => 'pending',
            'start_date'    => now()->addDays(3)->format('Y-m-d'),
            'end_date'      => now()->addDays(4)->format('Y-m-d'),
            'days_count'    => 2,
        ]);

        $this->actingAs($this->user)
            ->post('/my-leave', annualLeavePayload())
            ->assertSessionHasErrors('start_date');
    });

    test('it rejects sick leave 3+ days without medical certificate', function () {
        // 5 calendar-day span (Mon-Fri or similar) triggers the cert requirement
        $start = now()->addDays(7)->format('Y-m-d');
        $end   = now()->addDays(11)->format('Y-m-d');  // +4 more = 5-day span

        $this->actingAs($this->user)
            ->post('/my-leave', [
                'leave_type_id' => $this->sickType->id,
                'start_date'    => $start,
                'end_date'      => $end,
                'reason'        => 'Flu',
            ])
            ->assertSessionHasErrors('attachment');
    });

    test('it rejects study leave with less than 10 days advance notice', function () {
        $this->actingAs($this->user)
            ->post('/my-leave', [
                'leave_type_id' => $this->studyType->id,
                'start_date'    => now()->addDays(5)->format('Y-m-d'),
                'end_date'      => now()->addDays(5)->format('Y-m-d'),
                'reason'        => 'Exam',
            ])
            ->assertSessionHasErrors('start_date');
    });

    test('it rejects maternity leave for male employees', function () {
        // This employee is male
        $this->actingAs($this->user)
            ->post('/my-leave', [
                'leave_type_id' => $this->maternityType->id,
                'start_date'    => now()->addDays(3)->format('Y-m-d'),
                'end_date'      => now()->addDays(10)->format('Y-m-d'),
                'reason'        => 'Birth',
            ])
            ->assertSessionHasErrors('leave_type_id');
    });

    test('it accepts sick leave with valid certificate attachment', function () {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('cert.pdf', 100, 'application/pdf');

        $this->actingAs($this->user)
            ->post('/my-leave', [
                'leave_type_id' => $this->sickType->id,
                'start_date'    => now()->addDay()->format('Y-m-d'),
                'end_date'      => now()->addDays(4)->format('Y-m-d'),
                'reason'        => 'Flu',
                'attachment'    => $file,
            ])
            ->assertRedirect();

        expect(LeaveRequest::where('employee_id', $this->employee->id)->exists())->toBeTrue();
    });

    test('it rejects attachments over 10 MB', function () {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('huge.pdf', 11_001, 'application/pdf');

        $this->actingAs($this->user)
            ->post('/my-leave', [
                'leave_type_id' => $this->sickType->id,
                'start_date'    => now()->addDay()->format('Y-m-d'),
                'end_date'      => now()->addDays(4)->format('Y-m-d'),
                'reason'        => 'Flu',
                'attachment'    => $file,
            ])
            ->assertSessionHasErrors('attachment');
    });
});

// ─── Cancel ───────────────────────────────────────────────────────────────────

describe('my-leave — cancel', function () {
    test('it cancels a pending request', function () {
        $req = LeaveRequest::factory()->create([
            'org_id'        => $this->org->id,
            'employee_id'   => $this->employee->id,
            'leave_type_id' => $this->annualType->id,
            'status'        => 'pending',
            'start_date'    => now()->addDays(10)->format('Y-m-d'),
            'end_date'      => now()->addDays(11)->format('Y-m-d'),
            'days_count'    => 2,
        ]);

        $this->actingAs($this->user)
            ->post("/my-leave/{$req->id}/cancel")
            ->assertRedirect();

        $req->refresh();
        expect($req->status)->toBe('cancelled');
        expect($req->cancelled_at)->not->toBeNull();
    });

    test('it blocks cancelling another employees request', function () {
        $otherEmp = Employee::factory()->create([
            'org_id'     => $this->org->id,
            'office_id'  => $this->office->id,
            'department_id' => $this->dept->id,
            'position_id'   => $this->pos->id,
        ]);
        $req = LeaveRequest::factory()->create([
            'org_id'        => $this->org->id,
            'employee_id'   => $otherEmp->id,
            'leave_type_id' => $this->annualType->id,
            'status'        => 'pending',
            'start_date'    => now()->addDays(10)->format('Y-m-d'),
            'end_date'      => now()->addDays(11)->format('Y-m-d'),
            'days_count'    => 2,
        ]);

        $this->actingAs($this->user)
            ->post("/my-leave/{$req->id}/cancel")
            ->assertForbidden();
    });
});

// ─── Workday counter ─────────────────────────────────────────────────────────

describe('my-leave — workday counter', function () {
    test('it excludes weekends from day count', function () {
        // Future Sun-Sat = 5 workdays (Sun/Mon/Tue/Wed/Thu; Fri+Sat excluded)
        $nextSun = \Carbon\Carbon::now()->addWeeks(4)->startOfWeek(\Carbon\Carbon::SUNDAY);
        $start   = $nextSun->format('Y-m-d');
        $end     = $nextSun->copy()->addDays(6)->format('Y-m-d');  // Sun to Sat

        $this->actingAs($this->user)
            ->post('/my-leave', annualLeavePayload(['start_date' => $start, 'end_date' => $end]))
            ->assertRedirect();

        $req = LeaveRequest::where('employee_id', $this->employee->id)->first();
        expect($req->days_count)->toBe(5);
    });

    test('it excludes public holidays from day count', function () {
        // Future Sun-Tue with Monday as holiday = 2 workdays (Sun + Tue; Mon excluded)
        $nextSun = \Carbon\Carbon::now()->addWeeks(4)->startOfWeek(\Carbon\Carbon::SUNDAY);
        $start   = $nextSun->format('Y-m-d');
        $end     = $nextSun->copy()->addDays(2)->format('Y-m-d');  // Sun to Tue
        $monDate = $nextSun->copy()->addDay()->format('Y-m-d');    // Monday

        Holiday::factory()->create([
            'org_id' => $this->org->id,
            'date'   => $monDate,
        ]);

        $this->actingAs($this->user)
            ->post('/my-leave', annualLeavePayload(['start_date' => $start, 'end_date' => $end]))
            ->assertRedirect();

        $req = LeaveRequest::where('employee_id', $this->employee->id)->first();
        // Sun + Mon (holiday, excluded) + Tue = 2 workdays
        expect($req->days_count)->toBe(2);
    });
});

// ─── Multi-tenancy ───────────────────────────────────────────────────────────

describe('my-leave — multi-tenancy', function () {
    test('it only returns the authenticated employees own leave requests', function () {
        // Create another org's leave request
        $otherOrg  = Organization::factory()->create();
        $otherOffice = Office::factory()->create(['org_id' => $otherOrg->id]);
        $otherDept = Department::factory()->create(['org_id' => $otherOrg->id]);
        $otherPos  = Position::factory()->create([
            'org_id'        => $otherOrg->id,
            'department_id' => $otherDept->id,
        ]);
        $otherEmp = Employee::factory()->create([
            'org_id'     => $otherOrg->id,
            'office_id'  => $otherOffice->id,
            'department_id' => $otherDept->id,
            'position_id'   => $otherPos->id,
        ]);
        $otherType = LeaveType::factory()->create(['org_id' => $otherOrg->id, 'code' => 'annual']);
        LeaveRequest::factory()->create([
            'org_id'        => $otherOrg->id,
            'employee_id'   => $otherEmp->id,
            'leave_type_id' => $otherType->id,
            'status'        => 'pending',
            'start_date'    => now()->addDays(5)->format('Y-m-d'),
            'end_date'      => now()->addDays(6)->format('Y-m-d'),
            'days_count'    => 2,
        ]);

        // Our user has no requests
        $this->actingAs($this->user)
            ->get('/my-leave')
            ->assertInertia(fn ($p) => $p->has('requests.data', 0));
    });
});

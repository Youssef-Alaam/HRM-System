<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Office;
use App\Models\Organization;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
});

function makeScheduleEmployee(array $attrs = []): Employee
{
    $t = test();

    $emp = Employee::factory()->create(array_merge([
        'org_id'          => $t->org->id,
        'office_id'       => $t->office->id,
        'department_id'   => $t->dept->id,
        'position_id'     => $t->pos->id,
        'employment_status' => 'active',
        'workweek_days'   => ['sun', 'mon', 'tue', 'wed', 'thu'],
        'shift_start_time' => '09:00',
        'shift_end_time'  => '17:00',
    ], $attrs));

    $t->user->update(['employee_id' => $emp->id]);

    return $emp;
}

describe('schedule — auth', function () {
    test('it redirects guests to login', function () {
        $this->get('/schedule')->assertRedirect('/login');
    });

    test('it renders for any authenticated employee', function () {
        makeScheduleEmployee();

        $this->actingAs($this->user)
            ->get('/schedule')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Schedule'));
    });

    test('it blocks users without attendance.view.own permission', function () {
        $noPermsUser = User::factory()->create(['org_id' => $this->org->id]);
        // deliberately no role assigned

        $this->actingAs($noPermsUser)
            ->get('/schedule')
            ->assertForbidden();
    });
});

describe('schedule — data shape', function () {
    test('it returns 7 days for the current week', function () {
        makeScheduleEmployee();

        $this->actingAs($this->user)
            ->get('/schedule')
            ->assertInertia(fn ($p) => $p
                ->component('Schedule')
                ->has('days', 7)
                ->has('week_start')
                ->has('week_end')
                ->has('prev_week')
                ->has('next_week')
            );
    });

    test('it marks workweek days correctly', function () {
        makeScheduleEmployee([
            'workweek_days' => ['sun', 'mon', 'tue', 'wed', 'thu'],
        ]);

        $this->actingAs($this->user)
            ->get('/schedule')
            ->assertInertia(function ($p) {
                $days = $p->toArray()['props']['days'];

                $workdayNames = array_map(fn ($d) => strtolower($d['day_name']), array_filter($days, fn ($d) => $d['is_workday']));
                expect($workdayNames)->toContain('sun')
                    ->toContain('mon')
                    ->toContain('tue')
                    ->toContain('wed')
                    ->toContain('thu');

                $offDayNames = array_map(fn ($d) => strtolower($d['day_name']), array_filter($days, fn ($d) => ! $d['is_workday']));
                expect($offDayNames)->toContain('fri')->toContain('sat');
            });
    });

    test('it exposes shift hours from the employee record', function () {
        makeScheduleEmployee([
            'shift_start_time' => '08:00',
            'shift_end_time'   => '16:00',
        ]);

        $this->actingAs($this->user)
            ->get('/schedule')
            ->assertInertia(fn ($p) => $p
                ->where('employee.shift_start', '08:00')
                ->where('employee.shift_end', '16:00')
            );
    });

    test('it returns null employee when user has no employee record', function () {
        // user has no employee_id
        $this->actingAs($this->user)
            ->get('/schedule')
            ->assertInertia(fn ($p) => $p->where('employee', null));
    });

    test('it navigates to a specific week via ?week param', function () {
        makeScheduleEmployee();

        // Ask for the week starting 2026-01-04 (a Sunday)
        $this->actingAs($this->user)
            ->get('/schedule?week=2026-01-04')
            ->assertInertia(fn ($p) => $p
                ->where('week_start', '2026-01-04')
                ->where('week_end', '2026-01-10')
            );
    });

    test('it falls back to current week on invalid ?week param', function () {
        makeScheduleEmployee();

        $this->actingAs($this->user)
            ->get('/schedule?week=not-a-date')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->has('week_start'));
    });
});

describe('schedule — holidays', function () {
    test('it shows org holidays on their date', function () {
        makeScheduleEmployee();

        // Pin to a known week: Sun 2026-01-04 .. Sat 2026-01-10
        $holiday = Holiday::factory()->create([
            'org_id' => $this->org->id,
            'date'   => '2026-01-05', // Monday
            'name'   => 'Test Holiday',
        ]);

        $this->actingAs($this->user)
            ->get('/schedule?week=2026-01-04')
            ->assertInertia(function ($p) use ($holiday) {
                $days = $p->toArray()['props']['days'];
                $monday = collect($days)->firstWhere('date', '2026-01-05');
                expect($monday)->not->toBeNull()
                    ->and($monday['holiday'])->not->toBeNull()
                    ->and($monday['holiday']['id'])->toBe($holiday->id)
                    ->and($monday['holiday']['name'])->toBe('Test Holiday');
            });
    });

    test('it does not show holidays from another org', function () {
        makeScheduleEmployee();

        $otherOrg = Organization::factory()->create();
        Holiday::factory()->create([
            'org_id' => $otherOrg->id,
            'date'   => '2026-01-05',
            'name'   => 'Other Org Holiday',
        ]);

        $this->actingAs($this->user)
            ->get('/schedule?week=2026-01-04')
            ->assertInertia(function ($p) {
                $days = $p->toArray()['props']['days'];
                $monday = collect($days)->firstWhere('date', '2026-01-05');
                expect($monday['holiday'])->toBeNull();
            });
    });
});

describe('schedule — multi-tenancy', function () {
    test('it only shows the authenticated users own schedule', function () {
        $otherOrg    = Organization::factory()->create();
        $otherOffice = Office::factory()->create(['org_id' => $otherOrg->id]);
        $otherDept   = Department::factory()->create(['org_id' => $otherOrg->id]);
        $otherPos    = Position::factory()->create([
            'org_id'        => $otherOrg->id,
            'department_id' => $otherDept->id,
        ]);
        $otherEmp = Employee::factory()->create([
            'org_id'           => $otherOrg->id,
            'office_id'        => $otherOffice->id,
            'department_id'    => $otherDept->id,
            'position_id'      => $otherPos->id,
            'shift_start_time' => '06:00',
            'shift_end_time'   => '14:00',
        ]);

        // Our user should NOT see the other org's employee shifts
        makeScheduleEmployee([
            'shift_start_time' => '09:00',
            'shift_end_time'   => '17:00',
        ]);

        $this->actingAs($this->user)
            ->get('/schedule')
            ->assertInertia(fn ($p) => $p
                ->where('employee.shift_start', '09:00')
                ->where('employee.shift_end', '17:00')
            );
    });
});

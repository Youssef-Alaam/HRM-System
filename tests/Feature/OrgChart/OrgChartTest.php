<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Organization;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->org = Organization::factory()->create();
    $this->office = Office::factory()->create(['org_id' => $this->org->id]);
    $this->dept = Department::factory()->create(['org_id' => $this->org->id]);
    $this->position = Position::factory()->create([
        'org_id' => $this->org->id,
        'department_id' => $this->dept->id,
    ]);

    $this->user = User::factory()->create(['org_id' => $this->org->id]);
    $this->user->assignRole('employee');
});

function makeEmployee(array $attrs = []): Employee
{
    /** @var TestCase $t */
    $t = test();

    return Employee::factory()->create(array_merge([
        'org_id' => $t->org->id,
        'office_id' => $t->office->id,
        'department_id' => $t->dept->id,
        'position_id' => $t->position->id,
        'employment_status' => 'active',
    ], $attrs));
}

describe('org-chart — auth', function () {
    test('it redirects guests to login', function () {
        $this->get('/org-chart')->assertRedirect('/login');
    });

    test('it renders for any authenticated user', function () {
        $this->actingAs($this->user)
            ->get('/org-chart')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('OrgChart'));
    });
});

describe('org-chart — tree shape', function () {
    test('it returns NULL-manager employees as roots', function () {
        $ceo = makeEmployee(['manager_id' => null, 'first_name' => 'Cee']);
        makeEmployee(['manager_id' => $ceo->id, 'first_name' => 'Direct']);

        $this->actingAs($this->user)
            ->get('/org-chart')
            ->assertInertia(
                fn ($page) => $page
                    ->component('OrgChart')
                    ->has('roots', 1)
                    ->where('roots.0.first_name', 'Cee')
                    ->has('roots.0.children', 1)
                    ->where('roots.0.children.0.first_name', 'Direct'),
            );
    });

    test('it nests reports recursively', function () {
        $ceo = makeEmployee(['manager_id' => null]);
        $vp = makeEmployee(['manager_id' => $ceo->id]);
        $eng = makeEmployee(['manager_id' => $vp->id]);
        makeEmployee(['manager_id' => $eng->id]);

        $this->actingAs($this->user)
            ->get('/org-chart')
            ->assertInertia(
                fn ($page) => $page
                    ->where('roots.0.children.0.children.0.children.0.id', fn ($id) => is_int($id)),
            );
    });

    test('it returns empty roots when there are no employees', function () {
        $this->actingAs($this->user)
            ->get('/org-chart')
            ->assertInertia(fn ($page) => $page->where('roots', []));
    });
});

describe('org-chart — inactive skip-up', function () {
    test('it excludes terminated employees and re-parents their reports to the manager above', function () {
        $ceo = makeEmployee(['manager_id' => null, 'first_name' => 'Top']);
        $terminated = makeEmployee([
            'manager_id' => $ceo->id,
            'employment_status' => 'terminated',
            'first_name' => 'Gone',
        ]);
        $report = makeEmployee([
            'manager_id' => $terminated->id,
            'first_name' => 'Surviving',
        ]);

        $this->actingAs($this->user)
            ->get('/org-chart')
            ->assertInertia(function ($page) use ($report) {
                $page->where('roots.0.first_name', 'Top')
                    ->has('roots.0.children', 1)
                    ->where('roots.0.children.0.id', $report->id)
                    ->where('roots.0.children.0.first_name', 'Surviving');
            });
    });
});

describe('org-chart — multi-tenancy', function () {
    test('it does not leak employees from another org', function () {
        makeEmployee(['manager_id' => null, 'first_name' => 'InsideRoot']);

        $otherOrg = Organization::factory()->create();
        $otherOffice = Office::factory()->create(['org_id' => $otherOrg->id]);
        $otherDept = Department::factory()->create(['org_id' => $otherOrg->id]);
        $otherPosition = Position::factory()->create([
            'org_id' => $otherOrg->id,
            'department_id' => $otherDept->id,
        ]);
        Employee::factory()->create([
            'org_id' => $otherOrg->id,
            'office_id' => $otherOffice->id,
            'department_id' => $otherDept->id,
            'position_id' => $otherPosition->id,
            'manager_id' => null,
            'first_name' => 'OutsideRoot',
            'employment_status' => 'active',
        ]);

        $this->actingAs($this->user)
            ->get('/org-chart')
            ->assertInertia(
                fn ($page) => $page
                    ->has('roots', 1)
                    ->where('roots.0.first_name', 'InsideRoot'),
            );
    });
});

describe('org-chart — cycle safety', function () {
    test('it does not infinite-loop when two managers point at each other', function () {
        $a = makeEmployee(['manager_id' => null]);
        $b = makeEmployee(['manager_id' => $a->id]);
        // Force a cycle: A reports to B, B reports to A.
        $a->update(['manager_id' => $b->id]);

        $this->actingAs($this->user)
            ->get('/org-chart')
            ->assertOk();
    });
});

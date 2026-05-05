<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->org = Organization::factory()->create();
    $this->admin = User::factory()->create(['org_id' => $this->org->id]);
    $this->admin->assignRole('admin');
    $this->hr = User::factory()->create(['org_id' => $this->org->id]);
    $this->hr->assignRole('hr');
    $this->employee = User::factory()->create(['org_id' => $this->org->id]);
    $this->employee->assignRole('employee');
});

describe('Departments — auth & gating', function () {
    test('guest is redirected to login', function () {
        $this->get('/departments')->assertRedirect('/login');
    });

    test('employee cannot access departments settings', function () {
        $this->actingAs($this->employee)->get('/departments')->assertForbidden();
    });

    test('hr can access departments settings', function () {
        $this->actingAs($this->hr)->get('/departments')->assertOk()
            ->assertInertia(fn ($p) => $p->component('Settings/Departments'));
    });

    test('admin can access departments settings', function () {
        $this->actingAs($this->admin)->get('/departments')->assertOk();
    });
});

describe('Departments — CRUD', function () {
    test('admin can create a department', function () {
        $this->actingAs($this->admin)
            ->post('/departments', ['name' => 'Engineering'])
            ->assertRedirect();

        $this->assertDatabaseHas('departments', ['name' => 'Engineering', 'org_id' => $this->org->id]);
    });

    test('name is required', function () {
        $this->actingAs($this->admin)
            ->post('/departments', ['name' => ''])
            ->assertSessionHasErrors('name');
    });

    test('name is unique per org', function () {
        Department::factory()->create(['org_id' => $this->org->id, 'name' => 'Engineering']);

        $this->actingAs($this->admin)
            ->post('/departments', ['name' => 'Engineering'])
            ->assertSessionHasErrors('name');
    });

    test('admin can update a department', function () {
        $dept = Department::factory()->create(['org_id' => $this->org->id, 'name' => 'Old']);

        $this->actingAs($this->admin)
            ->patch("/departments/{$dept->id}", ['name' => 'New'])
            ->assertRedirect();

        expect($dept->fresh()->name)->toBe('New');
    });

    test('admin can soft-delete a department with no employees', function () {
        $dept = Department::factory()->create(['org_id' => $this->org->id]);

        $this->actingAs($this->admin)
            ->delete("/departments/{$dept->id}")
            ->assertRedirect();

        $this->assertSoftDeleted('departments', ['id' => $dept->id]);
    });

    test('cannot delete a department that has active employees', function () {
        $dept = Department::factory()->create(['org_id' => $this->org->id]);
        Employee::factory()->create(['org_id' => $this->org->id, 'department_id' => $dept->id, 'employment_status' => 'active']);

        $this->actingAs($this->admin)
            ->delete("/departments/{$dept->id}")
            ->assertSessionHasErrors();
    });

    test('employee cannot create a department', function () {
        $this->actingAs($this->employee)
            ->post('/departments', ['name' => 'Hack'])
            ->assertForbidden();
    });

    test('departments are scoped to org', function () {
        $otherOrg = Organization::factory()->create();
        Department::factory()->create(['org_id' => $otherOrg->id, 'name' => 'GhostDept']);
        Department::factory()->create(['org_id' => $this->org->id, 'name' => 'RealDept']);

        $response = $this->actingAs($this->hr)->get('/departments');
        $response->assertOk();
        $names = collect($response->original->getData()['page']['props']['departments'])->pluck('name');
        expect($names)->toContain('RealDept');
        expect($names)->not->toContain('GhostDept');
    });
});

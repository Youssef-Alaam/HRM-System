<?php

use App\Models\Department;
use App\Models\Organization;
use App\Models\Position;
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
    $this->dept = Department::factory()->create(['org_id' => $this->org->id]);
});

describe('Positions — auth & gating', function () {
    test('guest is redirected to login', function () {
        $this->get('/positions')->assertRedirect('/login');
    });

    test('employee cannot access positions settings', function () {
        $this->actingAs($this->employee)->get('/positions')->assertForbidden();
    });

    test('hr can access positions settings', function () {
        $this->actingAs($this->hr)->get('/positions')->assertOk()
            ->assertInertia(fn ($p) => $p->component('Settings/Positions'));
    });
});

describe('Positions — CRUD', function () {
    test('admin can create a position', function () {
        $this->actingAs($this->admin)
            ->post('/positions', [
                'title' => 'Senior Engineer',
                'department_id' => $this->dept->id,
                'level' => 3,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('positions', ['title' => 'Senior Engineer', 'org_id' => $this->org->id]);
    });

    test('title and department_id are required', function () {
        $this->actingAs($this->admin)
            ->post('/positions', [])
            ->assertSessionHasErrors(['title', 'department_id']);
    });

    test('admin can update a position', function () {
        $pos = Position::factory()->create(['org_id' => $this->org->id, 'department_id' => $this->dept->id, 'title' => 'Old']);

        $this->actingAs($this->admin)
            ->patch("/positions/{$pos->id}", ['title' => 'New', 'department_id' => $this->dept->id])
            ->assertRedirect();

        expect($pos->fresh()->title)->toBe('New');
    });

    test('admin can soft-delete a position', function () {
        $pos = Position::factory()->create(['org_id' => $this->org->id, 'department_id' => $this->dept->id]);

        $this->actingAs($this->admin)->delete("/positions/{$pos->id}")->assertRedirect();

        $this->assertSoftDeleted('positions', ['id' => $pos->id]);
    });

    test('positions are scoped to org', function () {
        $otherOrg = Organization::factory()->create();
        $otherDept = Department::factory()->create(['org_id' => $otherOrg->id]);
        Position::factory()->create(['org_id' => $otherOrg->id, 'department_id' => $otherDept->id, 'title' => 'GhostPos']);
        Position::factory()->create(['org_id' => $this->org->id, 'department_id' => $this->dept->id, 'title' => 'RealPos']);

        $response = $this->actingAs($this->hr)->get('/positions');
        $response->assertOk();
        $titles = collect($response->original->getData()['page']['props']['positions'])->pluck('title');
        expect($titles)->toContain('RealPos');
        expect($titles)->not->toContain('GhostPos');
    });
});

<?php

use App\Models\AuditLog;
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

describe('Audit Log — auth & gating', function () {
    test('guest is redirected to login', function () {
        $this->get('/admin/audit-log')->assertRedirect('/login');
    });

    test('employee is forbidden', function () {
        $this->actingAs($this->employee)->get('/admin/audit-log')->assertForbidden();
    });

    test('hr is forbidden', function () {
        $this->actingAs($this->hr)->get('/admin/audit-log')->assertForbidden();
    });

    test('admin can view audit log', function () {
        $this->actingAs($this->admin)->get('/admin/audit-log')->assertOk()
            ->assertInertia(fn ($p) => $p->component('AuditLog/Index'));
    });
});

describe('Audit Log — data', function () {
    test('it returns paginated audit entries for the org', function () {
        AuditLog::insert([
            'org_id' => $this->org->id,
            'user_id' => $this->admin->id,
            'action' => 'created',
            'entity_type' => 'App\\Models\\Employee',
            'entity_id' => '1',
            'created_at' => now(),
        ]);

        $this->actingAs($this->admin)->get('/admin/audit-log')
            ->assertInertia(fn ($p) => $p
                ->has('entries.data', 1)
                ->where('entries.data.0.action', 'created'),
            );
    });

    test('it does not show entries from another org', function () {
        $otherOrg = Organization::factory()->create();
        AuditLog::insert([
            'org_id' => $otherOrg->id,
            'action' => 'created',
            'entity_type' => 'App\\Models\\Employee',
            'entity_id' => '99',
            'created_at' => now(),
        ]);

        $this->actingAs($this->admin)->get('/admin/audit-log')
            ->assertInertia(fn ($p) => $p->has('entries.data', 0));
    });

    test('it can filter by action', function () {
        AuditLog::insert([
            ['org_id' => $this->org->id, 'user_id' => $this->admin->id, 'action' => 'created', 'entity_type' => 'App\\Models\\Employee', 'entity_id' => '1', 'created_at' => now()],
            ['org_id' => $this->org->id, 'user_id' => $this->admin->id, 'action' => 'deleted', 'entity_type' => 'App\\Models\\Employee', 'entity_id' => '2', 'created_at' => now()],
        ]);

        $this->actingAs($this->admin)->get('/admin/audit-log?action=created')
            ->assertInertia(fn ($p) => $p
                ->has('entries.data', 1)
                ->where('entries.data.0.action', 'created'),
            );
    });
});

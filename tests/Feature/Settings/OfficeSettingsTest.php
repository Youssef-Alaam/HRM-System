<?php

use App\Models\Office;
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

describe('Offices — auth & gating', function () {
    test('guest is redirected to login', function () {
        $this->get('/offices')->assertRedirect('/login');
    });

    test('employee cannot access offices settings', function () {
        $this->actingAs($this->employee)->get('/offices')->assertForbidden();
    });

    test('hr can access offices settings', function () {
        $this->actingAs($this->hr)->get('/offices')->assertOk()
            ->assertInertia(fn ($p) => $p->component('Settings/Offices'));
    });
});

describe('Offices — CRUD', function () {
    test('admin can create an office', function () {
        $this->actingAs($this->admin)
            ->post('/offices', [
                'name' => 'Cairo HQ',
                'address' => '10 Tahrir Square, Cairo',
                'latitude' => 30.0444,
                'longitude' => 31.2357,
                'allowed_check_in_radius_meters' => 200,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('offices', ['name' => 'Cairo HQ', 'org_id' => $this->org->id]);
    });

    test('name and radius are required', function () {
        $this->actingAs($this->admin)
            ->post('/offices', [])
            ->assertSessionHasErrors(['name', 'allowed_check_in_radius_meters']);
    });

    test('latitude must be between -90 and 90 when provided', function () {
        $this->actingAs($this->admin)
            ->post('/offices', [
                'name' => 'Bad',
                'latitude' => 200,
                'longitude' => 31.2,
                'allowed_check_in_radius_meters' => 100,
            ])
            ->assertSessionHasErrors('latitude');
    });

    test('admin can update an office', function () {
        $office = Office::factory()->create(['org_id' => $this->org->id, 'name' => 'Old Name']);

        $this->actingAs($this->admin)
            ->patch("/offices/{$office->id}", [
                'name' => 'New Name',
                'latitude' => $office->latitude,
                'longitude' => $office->longitude,
                'allowed_check_in_radius_meters' => $office->allowed_check_in_radius_meters,
            ])
            ->assertRedirect();

        expect($office->fresh()->name)->toBe('New Name');
    });

    test('admin can soft-delete an office', function () {
        $office = Office::factory()->create(['org_id' => $this->org->id]);

        $this->actingAs($this->admin)->delete("/offices/{$office->id}")->assertRedirect();

        $this->assertSoftDeleted('offices', ['id' => $office->id]);
    });

    test('offices are scoped to org', function () {
        $otherOrg = Organization::factory()->create();
        Office::factory()->create(['org_id' => $otherOrg->id, 'name' => 'GhostOffice']);
        Office::factory()->create(['org_id' => $this->org->id, 'name' => 'RealOffice']);

        $response = $this->actingAs($this->hr)->get('/offices');
        $response->assertOk();
        $names = collect($response->original->getData()['page']['props']['offices'])->pluck('name');
        expect($names)->toContain('RealOffice');
        expect($names)->not->toContain('GhostOffice');
    });
});

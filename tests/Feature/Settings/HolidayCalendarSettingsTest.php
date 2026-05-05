<?php

use App\Models\Holiday;
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

describe('Holiday Calendar — auth & gating', function () {
    test('guest is redirected to login', function () {
        $this->get('/holidays')->assertRedirect('/login');
    });

    test('employee cannot manage holidays', function () {
        $this->actingAs($this->employee)->get('/holidays')->assertForbidden();
    });

    test('hr can view the holiday calendar settings page', function () {
        $this->actingAs($this->hr)->get('/holidays')->assertOk()
            ->assertInertia(fn ($p) => $p->component('Settings/HolidayCalendar'));
    });
});

describe('Holiday Calendar — CRUD', function () {
    test('hr can create a holiday', function () {
        $this->actingAs($this->hr)
            ->post('/admin/holidays', [
                'name' => 'Test Holiday',
                'date' => '2026-12-25',
                'is_make_up' => false,
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('holidays', ['name' => 'Test Holiday', 'org_id' => $this->org->id]);
    });

    test('holidays are scoped to org', function () {
        $otherOrg = Organization::factory()->create();
        $otherUser = User::factory()->create(['org_id' => $otherOrg->id]);
        $otherUser->assignRole('hr');

        Holiday::factory()->create(['org_id' => $otherOrg->id, 'name' => 'Ghost Holiday', 'date' => '2026-06-01']);
        Holiday::factory()->create(['org_id' => $this->org->id, 'name' => 'Real Holiday', 'date' => '2026-06-02']);

        $response = $this->actingAs($this->hr)->get('/holidays');
        $response->assertOk();
        $names = collect($response->original->getData()['page']['props']['holidays'])->pluck('name');
        expect($names)->toContain('Real Holiday');
        expect($names)->not->toContain('Ghost Holiday');
    });
});

<?php

use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Services\ComplianceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

// ── Access control ────────────────────────────────────────────────────────────

test('guest is redirected to login', function () {
    $this->get('/compliance')->assertRedirect('/login');
});

test('employee cannot access compliance dashboard', function () {
    actingAsRole('employee');
    $this->get('/compliance')->assertForbidden();
});

test('manager cannot access compliance dashboard', function () {
    actingAsRole('manager');
    $this->get('/compliance')->assertForbidden();
});

test('hr can access compliance dashboard', function () {
    actingAsRole('hr');
    $this->get('/compliance')->assertOk();
});

test('admin can access compliance dashboard', function () {
    actingAsRole('admin');
    $this->get('/compliance')->assertOk();
});

// ── Probation status ──────────────────────────────────────────────────────────

test('probation panel shows active probation employees', function () {
    $org = Organization::factory()->create();
    actingAsRole('hr', $org);

    Employee::factory()->create([
        'org_id' => $org->id,
        'contract_type' => 'probation',
        'employment_status' => 'active',
        'contract_start_date' => now()->subDays(30)->format('Y-m-d'),
        'first_name' => 'OnProbation',
        'last_name' => 'Employee',
    ]);

    $response = $this->get('/compliance');
    $props = $response->original->getData()['page']['props'];

    $names = collect($props['probation'])->pluck('name');
    expect($names)->toContain('OnProbation Employee');
});

test('probation alert level is warning within 15 days', function () {
    $org = Organization::factory()->create();
    actingAsRole('hr', $org);

    Employee::factory()->create([
        'org_id' => $org->id,
        'contract_type' => 'probation',
        'employment_status' => 'active',
        // 80 days into probation = 10 days remaining
        'contract_start_date' => now()->subDays(80)->format('Y-m-d'),
        'first_name' => 'Warning',
        'last_name' => 'Employee',
    ]);

    $response = $this->get('/compliance');
    $props = $response->original->getData()['page']['props'];
    $emp = collect($props['probation'])->firstWhere('name', 'Warning Employee');

    expect($emp['alert_level'])->toBe('warning');
});

test('probation is overdue after 90 days', function () {
    $org = Organization::factory()->create();
    actingAsRole('hr', $org);

    Employee::factory()->create([
        'org_id' => $org->id,
        'contract_type' => 'probation',
        'employment_status' => 'active',
        'contract_start_date' => now()->subDays(95)->format('Y-m-d'),
        'first_name' => 'Overdue',
        'last_name' => 'Employee',
    ]);

    $response = $this->get('/compliance');
    $props = $response->original->getData()['page']['props'];
    $emp = collect($props['probation'])->firstWhere('name', 'Overdue Employee');

    expect($emp['alert_level'])->toBe('overdue');
});

// ── Retirement alerts ─────────────────────────────────────────────────────────

test('retirement panel shows employees within 6 months of 60th birthday', function () {
    $org = Organization::factory()->create();
    actingAsRole('hr', $org);

    // Employee turning 60 in 3 months
    Employee::factory()->create([
        'org_id' => $org->id,
        'employment_status' => 'active',
        'date_of_birth' => Carbon::now()->subYears(60)->addMonths(3)->format('Y-m-d'),
        'first_name' => 'Retiring',
        'last_name' => 'Soon',
    ]);

    $response = $this->get('/compliance');
    $props = $response->original->getData()['page']['props'];

    $names = collect($props['retirement'])->pluck('name');
    expect($names)->toContain('Retiring Soon');
});

test('employees not within 6 months of retirement are not shown', function () {
    $org = Organization::factory()->create();
    actingAsRole('hr', $org);

    // Employee with retirement in 1 year
    Employee::factory()->create([
        'org_id' => $org->id,
        'employment_status' => 'active',
        'date_of_birth' => Carbon::now()->subYears(59)->format('Y-m-d'),
        'first_name' => 'NotYet',
        'last_name' => 'Retiring',
    ]);

    $response = $this->get('/compliance');
    $props = $response->original->getData()['page']['props'];

    $names = collect($props['retirement'])->pluck('name');
    expect($names)->not->toContain('NotYet Retiring');
});

// ── EOSB calculation ──────────────────────────────────────────────────────────

test('EOSB is zero for probation employees per Art. 41', function () {
    $service = app(ComplianceService::class);
    $emp = Employee::factory()->make([
        'contract_type' => 'probation',
        'base_salary_piasters' => 500000,
        'hiring_date' => now()->subMonths(2)->format('Y-m-d'),
    ]);

    expect($service->calculateEosb($emp))->toBe(0);
});

test('EOSB first 5 years is half-month per year per Art. 110', function () {
    $service = app(ComplianceService::class);
    $emp = Employee::factory()->make([
        'contract_type' => 'unlimited',
        'base_salary_piasters' => 600000, // EGP 6,000/month
        'hiring_date' => now()->subYears(3)->format('Y-m-d'),
    ]);

    $eosb = $service->calculateEosb($emp);
    // 3 years × 0.5 months × 600,000 piasters = 900,000 piasters
    expect($eosb)->toBeGreaterThanOrEqual(875000); // ±margin for partial year
    expect($eosb)->toBeLessThanOrEqual(925000);
});

test('EOSB above 5 years adds full-month per additional year', function () {
    $service = app(ComplianceService::class);
    $emp = Employee::factory()->make([
        'contract_type' => 'unlimited',
        'base_salary_piasters' => 600000, // EGP 6,000/month
        'hiring_date' => now()->subYears(7)->format('Y-m-d'),
    ]);

    $eosb = $service->calculateEosb($emp);
    // 5 years × 0.5 + 2 years × 1.0 = 2.5 + 2 = 4.5 months × 600,000 = 2,700,000
    expect($eosb)->toBeGreaterThanOrEqual(2600000);
    expect($eosb)->toBeLessThanOrEqual(2800000);
});

// ── Notice period ─────────────────────────────────────────────────────────────

test('notice period is 0 days for probation per Art. 41', function () {
    $service = app(ComplianceService::class);
    $emp = Employee::factory()->make(['contract_type' => 'probation']);
    expect($service->noticePeriodDays($emp))->toBe(0);
});

test('notice period is 90 days for unlimited contract per Art. 110', function () {
    $service = app(ComplianceService::class);
    $emp = Employee::factory()->make(['contract_type' => 'unlimited']);
    expect($service->noticePeriodDays($emp))->toBe(90);
});

test('notice period is 30 days for fixed contract', function () {
    $service = app(ComplianceService::class);
    $emp = Employee::factory()->make(['contract_type' => 'fixed']);
    expect($service->noticePeriodDays($emp))->toBe(30);
});

// ── Termination action ────────────────────────────────────────────────────────

test('hr can terminate an employee', function () {
    $org = Organization::factory()->create();
    actingAsRole('hr', $org);
    $emp = Employee::factory()->create(['org_id' => $org->id, 'employment_status' => 'active', 'contract_type' => 'unlimited', 'hiring_date' => now()->subYear()->format('Y-m-d')]);

    $this->post("/compliance/employees/{$emp->id}/terminate", [
        'reason' => 'resignation',
        'effective_date' => now()->addDays(90)->format('Y-m-d'),
    ])->assertRedirect();

    expect($emp->fresh()->employment_status)->toBe('terminated');
    expect($emp->fresh()->termination_reason)->toBe('resignation');
    expect($emp->fresh()->eosb_piasters)->toBeGreaterThan(0);
});

test('employee role cannot terminate', function () {
    $org = Organization::factory()->create();
    actingAsRole('employee', $org);
    $emp = Employee::factory()->create(['org_id' => $org->id]);

    $this->post("/compliance/employees/{$emp->id}/terminate", [
        'reason' => 'dismissal',
    ])->assertForbidden();
});

test('retirement termination sets status to retired', function () {
    $org = Organization::factory()->create();
    actingAsRole('hr', $org);
    $emp = Employee::factory()->create(['org_id' => $org->id, 'employment_status' => 'active', 'contract_type' => 'unlimited', 'hiring_date' => now()->subYears(5)->format('Y-m-d')]);

    $this->post("/compliance/employees/{$emp->id}/terminate", [
        'reason' => 'retirement',
    ])->assertRedirect();

    expect($emp->fresh()->employment_status)->toBe('retired');
});

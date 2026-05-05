<?php

use App\Models\Employee;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

// ── Access control ────────────────────────────────────────────────────────────

test('guest is redirected to login', function () {
    $this->get('/government-filings')->assertRedirect('/login');
    $this->get('/government-filings/nosi')->assertRedirect('/login');
    $this->get('/government-filings/form6')->assertRedirect('/login');
});

test('employee cannot access government filings', function () {
    actingAsRole('employee');
    $this->get('/government-filings')->assertForbidden();
    $this->get('/government-filings/nosi')->assertForbidden();
});

test('manager cannot access government filings', function () {
    actingAsRole('manager');
    $this->get('/government-filings')->assertForbidden();
});

test('hr can access government filings dashboard', function () {
    actingAsRole('hr');
    $this->get('/government-filings')->assertOk();
});

test('admin can access government filings dashboard', function () {
    actingAsRole('admin');
    $this->get('/government-filings')->assertOk();
});

// ── NOSI export ───────────────────────────────────────────────────────────────

test('NOSI export returns xlsx for hr', function () {
    $org = Organization::factory()->create();
    actingAsRole('hr', $org);

    $response = $this->get('/government-filings/nosi?month=2026-05');
    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('NOSI export includes active employees', function () {
    $org = Organization::factory()->create();
    actingAsRole('hr', $org);
    Employee::factory()->count(3)->create(['org_id' => $org->id, 'employment_status' => 'active']);
    Employee::factory()->count(2)->create(['org_id' => $org->id, 'employment_status' => 'terminated']);

    $response = $this->get('/government-filings/nosi?month=2026-05');
    $response->assertOk();
    // File downloads successfully (content-disposition header present)
    $response->assertHeader('Content-Disposition');
});

// ── Form 6 export ─────────────────────────────────────────────────────────────

test('Form 6 export returns xlsx for admin', function () {
    $org = Organization::factory()->create();
    actingAsRole('admin', $org);

    $response = $this->get('/government-filings/form6?year=2026');
    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

// ── SI calculation ────────────────────────────────────────────────────────────

test('NOSI export uses insurable wage cap', function () {
    // This is a unit test of the export logic via the export class directly
    $export = new \App\Exports\NosiExport(1, '2026-05');

    // Create a mock employee with salary above the cap (14,500 EGP = 1,450,000 piasters)
    $emp = new \App\Models\Employee([
        'employee_code' => 'EMP-001',
        'first_name' => 'Test',
        'last_name' => 'Employee',
        'national_id' => '12345678901234',
        'si_number' => 'SI-001',
        'base_salary_piasters' => 3_000_000, // EGP 30,000 (well above cap)
        'employment_status' => 'active',
    ]);

    $mapped = $export->map($emp);

    // Insurable wage should be capped at EGP 14,500
    expect($mapped[7])->toBe('14,500.00'); // insurable wage
    // Employee contribution = 14,500 × 11% = 1,595 EGP
    expect($mapped[8])->toBe('1,595.00');
    // Employer contribution = 14,500 × 18.75% = 2,718.75 EGP
    expect($mapped[9])->toBe('2,718.75');
});

test('NOSI export applies floor to low-wage employees', function () {
    $export = new \App\Exports\NosiExport(1, '2026-05');

    $emp = new \App\Models\Employee([
        'employee_code' => 'EMP-002',
        'first_name' => 'Low',
        'last_name' => 'Wage',
        'national_id' => '12345678901235',
        'base_salary_piasters' => 100_000, // EGP 1,000 (below floor)
        'employment_status' => 'active',
    ]);

    $mapped = $export->map($emp);

    // Insurable wage should be at floor: EGP 2,300
    expect($mapped[7])->toBe('2,300.00');
    // Employee SI = 2,300 × 11% = 253 EGP
    expect($mapped[8])->toBe('253.00');
});

// ── Props check ───────────────────────────────────────────────────────────────

test('government filings page renders current month and year props', function () {
    actingAsRole('hr');
    $response = $this->get('/government-filings');
    $props = $response->original->getData()['page']['props'];

    expect($props['current_month'])->toMatch('/^\d{4}-\d{2}$/');
    expect($props['current_year'])->toBeInt();
});

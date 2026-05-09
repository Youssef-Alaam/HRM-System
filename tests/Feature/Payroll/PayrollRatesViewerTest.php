<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->seed(\Database\Seeders\PayrollRatesSeeder::class);
});

test('guest is redirected to login', function () {
    $this->get('/admin/payroll-rates')->assertRedirect('/login');
});

test('employee cannot view payroll rates', function () {
    actingAsRole('employee');
    $this->get('/admin/payroll-rates')->assertForbidden();
});

test('hr cannot view payroll rates (settings.edit is admin-only)', function () {
    actingAsRole('hr');
    $this->get('/admin/payroll-rates')->assertForbidden();
});

test('admin can view payroll rates', function () {
    actingAsRole('admin');
    $this->get('/admin/payroll-rates')->assertOk();
});

test('viewer returns brackets for current year by default', function () {
    actingAsRole('admin');

    $response = $this->get('/admin/payroll-rates?year=2026');
    $props = $response->original->getData()['page']['props'];

    expect($props['year'])->toBe(2026);
    expect(count($props['brackets']))->toBe(8);
    expect($props['brackets'][0]['rate_pct'])->toBe(0.0);
    expect($props['brackets'][7]['rate_pct'])->toBe(30.0);
});

test('viewer returns settings constants for the year', function () {
    actingAsRole('admin');

    $response = $this->get('/admin/payroll-rates?year=2026');
    $props = $response->original->getData()['page']['props'];

    expect($props['settings'])->not->toBeNull();
    expect($props['settings']['personal_allowance_egp'])->toBe(20_000);
    expect($props['settings']['si_insurable_cap_egp'])->toBe(14_500);
    expect($props['settings']['si_employee_pct'])->toBe(11.0);
    expect($props['settings']['si_employer_pct'])->toBe(18.75);
    expect($props['settings']['minimum_wage_egp'])->toBe(7_000);
});

test('viewer returns null settings for unconfigured year', function () {
    actingAsRole('admin');

    $response = $this->get('/admin/payroll-rates?year=2030');
    $props = $response->original->getData()['page']['props'];

    expect($props['brackets'])->toHaveCount(0);
    expect($props['settings'])->toBeNull();
});

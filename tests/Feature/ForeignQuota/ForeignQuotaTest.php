<?php

use App\Models\Employee;
use App\Models\Organization;
use App\Services\ForeignQuotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

// ── Access control ────────────────────────────────────────────────────────────

test('guest is redirected to login', function () {
    $this->get('/foreign-quota')->assertRedirect('/login');
});

test('employee cannot access foreign quota dashboard', function () {
    actingAsRole('employee');
    $this->get('/foreign-quota')->assertForbidden();
});

test('hr can access foreign quota dashboard', function () {
    actingAsRole('hr');
    $this->get('/foreign-quota')->assertOk();
});

test('admin can access foreign quota dashboard', function () {
    actingAsRole('admin');
    $this->get('/foreign-quota')->assertOk();
});

// ── Quota calculation ─────────────────────────────────────────────────────────

test('headcount quota is green when expats are under 10%', function () {
    $service = app(ForeignQuotaService::class);
    $org = Organization::factory()->create();

    // 1 expat, 20 total = 5% (under 10%)
    Employee::factory()->count(19)->create(['org_id' => $org->id, 'is_expat' => false, 'employment_status' => 'active', 'base_salary_piasters' => 500000]);
    Employee::factory()->create(['org_id' => $org->id, 'is_expat' => true, 'employment_status' => 'active', 'base_salary_piasters' => 500000]);

    $status = $service->quotaStatus($org->id);

    expect($status['headcount_ok'])->toBeTrue();
    expect($status['expat_employees'])->toBe(1);
    expect($status['total_employees'])->toBe(20);
    expect($status['headcount_ratio'])->toBe(5.0);
});

test('headcount quota is red when expats exceed 10%', function () {
    $service = app(ForeignQuotaService::class);
    $org = Organization::factory()->create();

    // 3 expats, 10 total = 30% (above 10%)
    Employee::factory()->count(7)->create(['org_id' => $org->id, 'is_expat' => false, 'employment_status' => 'active', 'base_salary_piasters' => 500000]);
    Employee::factory()->count(3)->create(['org_id' => $org->id, 'is_expat' => true, 'employment_status' => 'active', 'base_salary_piasters' => 500000]);

    $status = $service->quotaStatus($org->id);

    expect($status['headcount_ok'])->toBeFalse();
    expect($status['status'])->toBe('red');
});

test('payroll quota is red when expat salary exceeds 35%', function () {
    $service = app(ForeignQuotaService::class);
    $org = Organization::factory()->create();

    // 1 expat on EGP 40k, 9 locals on EGP 5k each
    // expat payroll = 4,000,000 piasters; total = 4,000,000 + 4,500,000 = 8,500,000
    // ratio = 47% (above 35%)
    Employee::factory()->count(9)->create(['org_id' => $org->id, 'is_expat' => false, 'employment_status' => 'active', 'base_salary_piasters' => 500000]);
    Employee::factory()->create(['org_id' => $org->id, 'is_expat' => true, 'employment_status' => 'active', 'base_salary_piasters' => 4_000_000]);

    $status = $service->quotaStatus($org->id);

    expect($status['payroll_ok'])->toBeFalse();
});

test('canHireExpat returns false when at 10% headcount', function () {
    $service = app(ForeignQuotaService::class);
    $org = Organization::factory()->create();

    // Exactly 10%: 1 expat / 10 total
    Employee::factory()->count(9)->create(['org_id' => $org->id, 'is_expat' => false, 'employment_status' => 'active', 'base_salary_piasters' => 500000]);
    Employee::factory()->create(['org_id' => $org->id, 'is_expat' => true, 'employment_status' => 'active', 'base_salary_piasters' => 500000]);

    // Currently at exactly 10% — borderline (still ok since ≤ 10%)
    expect($service->canHireExpat($org->id))->toBeTrue();

    // One more expat would push to 18%
    Employee::factory()->create(['org_id' => $org->id, 'is_expat' => true, 'employment_status' => 'active', 'base_salary_piasters' => 500000]);
    expect($service->canHireExpat($org->id))->toBeFalse();
});

test('org isolation — quota only counts current org employees', function () {
    $service = app(ForeignQuotaService::class);
    $org = Organization::factory()->create();
    $otherOrg = Organization::factory()->create();

    // Other org has 20 expats (which would breach quota)
    Employee::factory()->count(20)->create(['org_id' => $otherOrg->id, 'is_expat' => true, 'employment_status' => 'active', 'base_salary_piasters' => 500000]);

    // Our org has only local employees
    Employee::factory()->count(10)->create(['org_id' => $org->id, 'is_expat' => false, 'employment_status' => 'active', 'base_salary_piasters' => 500000]);

    $status = $service->quotaStatus($org->id);

    expect($status['expat_employees'])->toBe(0);
    expect($status['headcount_ok'])->toBeTrue();
});

// ── Page props ────────────────────────────────────────────────────────────────

test('quota page renders correct props', function () {
    $org = Organization::factory()->create();
    actingAsRole('hr', $org);

    $response = $this->get('/foreign-quota');
    $props = $response->original->getData()['page']['props'];

    expect($props['quota'])->toHaveKeys(['total_employees', 'expat_employees', 'headcount_ratio', 'payroll_ratio', 'status']);
    expect($props['expats'])->toBeArray();
});

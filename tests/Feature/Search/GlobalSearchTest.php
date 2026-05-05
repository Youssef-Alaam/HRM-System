<?php

use App\Models\Announcement;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

test('guest is redirected to login', function () {
    $this->get('/search?q=test')->assertRedirect('/login');
});

test('returns empty results for queries shorter than 2 characters', function () {
    actingAsRole('employee');
    $response = $this->get('/search?q=A');
    $response->assertOk()->assertJson(['results' => []]);
});

test('employee can search', function () {
    actingAsRole('employee');
    $response = $this->get('/search?q=test');
    $response->assertOk()->assertJsonStructure(['results']);
});

test('hr can find employees by name', function () {
    $org = Organization::factory()->create();
    actingAsRole('hr', $org);

    Employee::factory()->create([
        'org_id' => $org->id,
        'first_name' => 'Ramadan',
        'last_name' => 'Hassan',
        'employment_status' => 'active',
    ]);

    $response = $this->getJson('/search?q=Ramadan');
    $data = $response->json();

    $labels = collect($data['results'])->pluck('label');
    expect($labels)->toContain('Ramadan Hassan');
});

test('employee cannot search employees by name (no view.any permission)', function () {
    $org = Organization::factory()->create();
    actingAsRole('employee', $org);

    Employee::factory()->create([
        'org_id' => $org->id,
        'first_name' => 'Hidden',
        'last_name' => 'Employee',
        'employment_status' => 'active',
    ]);

    $response = $this->getJson('/search?q=Hidden');
    $data = $response->json();

    $labels = collect($data['results'])->pluck('label');
    expect($labels)->not->toContain('Hidden Employee');
});

test('search results include published announcements for all roles', function () {
    $org = Organization::factory()->create();
    $admin = User::factory()->create(['org_id' => $org->id]);
    $admin->assignRole('admin');

    Announcement::factory()->create([
        'org_id' => $org->id,
        'author_id' => $admin->id,
        'title' => 'Ramadan schedule update',
        'body' => 'Shorter hours during Ramadan.',
        'published_at' => now()->subMinute(),
    ]);

    $user = actingAsRole('employee', $org);
    $response = $this->getJson('/search?q=Ramadan');
    $data = $response->json();

    $labels = collect($data['results'])->pluck('label');
    expect($labels)->toContain('Ramadan schedule update');
});

test('search does not return results from another org', function () {
    $org = Organization::factory()->create();
    $otherOrg = Organization::factory()->create();
    actingAsRole('hr', $org);

    Employee::factory()->create([
        'org_id' => $otherOrg->id,
        'first_name' => 'CrossOrg',
        'last_name' => 'Employee',
        'employment_status' => 'active',
    ]);

    $response = $this->getJson('/search?q=CrossOrg');
    $data = $response->json();

    $labels = collect($data['results'])->pluck('label');
    expect($labels)->not->toContain('CrossOrg Employee');
});

test('search returns at most 10 results total', function () {
    $org = Organization::factory()->create();
    actingAsRole('hr', $org);

    Employee::factory()->count(15)->create([
        'org_id' => $org->id,
        'first_name' => 'Mohamed',
        'employment_status' => 'active',
    ]);

    $response = $this->getJson('/search?q=Mohamed');
    $data = $response->json();

    expect(count($data['results']))->toBeLessThanOrEqual(10);
});

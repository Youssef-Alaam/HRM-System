<?php

use App\Models\Announcement;
use App\Models\Department;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
});

// ── Access control ────────────────────────────────────────────────────────────

test('guest is redirected to login', function () {
    $this->get('/announcements')->assertRedirect('/login');
});

test('employee can view announcements list', function () {
    actingAsRole('employee');
    $this->get('/announcements')->assertOk();
});

test('hr can view announcements list', function () {
    actingAsRole('hr');
    $this->get('/announcements')->assertOk();
});

test('manager can view announcements list', function () {
    actingAsRole('manager');
    $this->get('/announcements')->assertOk();
});

test('admin can view announcements list', function () {
    actingAsRole('admin');
    $this->get('/announcements')->assertOk();
});

// ── Create permission ─────────────────────────────────────────────────────────

test('employee cannot create an announcement', function () {
    actingAsRole('employee');
    $org = \App\Models\Organization::factory()->create();
    $recipient = User::factory()->create(['org_id' => $org->id]);

    $this->post('/announcements', [
        'title' => 'Hello',
        'body' => 'Body here.',
        'target_type' => 'all',
    ])->assertForbidden();
});

test('hr can create an announcement', function () {
    $org = Organization::factory()->create();
    $user = actingAsRole('hr', $org);

    $this->post('/announcements', [
        'title' => 'HR Notice',
        'body' => 'Please read.',
        'target_type' => 'all',
    ])->assertRedirect();

    expect(Announcement::where('org_id', $org->id)->where('title', 'HR Notice')->exists())->toBeTrue();
});

test('admin can create an announcement', function () {
    $org = Organization::factory()->create();
    $user = actingAsRole('admin', $org);

    $this->post('/announcements', [
        'title' => 'New Policy',
        'body' => 'Please read the updated policy.',
        'target_type' => 'all',
        'pinned' => false,
    ])->assertRedirect();

    expect(Announcement::where('org_id', $org->id)->where('title', 'New Policy')->exists())->toBeTrue();
});

test('manager cannot create an announcement', function () {
    actingAsRole('manager');

    $this->post('/announcements', [
        'title' => 'Team Meeting',
        'body' => 'Tomorrow at 10am.',
        'target_type' => 'all',
    ])->assertForbidden();
});

// ── Validation ────────────────────────────────────────────────────────────────

test('title is required', function () {
    actingAsRole('admin');

    $this->post('/announcements', [
        'title' => '',
        'body' => 'Body.',
        'target_type' => 'all',
    ])->assertSessionHasErrors('title');
});

test('body is required', function () {
    actingAsRole('admin');

    $this->post('/announcements', [
        'title' => 'Hi',
        'body' => '',
        'target_type' => 'all',
    ])->assertSessionHasErrors('body');
});

test('department target requires a valid department in same org', function () {
    $org = Organization::factory()->create();
    $otherOrg = Organization::factory()->create();
    actingAsRole('admin', $org);
    $foreignDept = Department::factory()->create(['org_id' => $otherOrg->id]);

    $this->post('/announcements', [
        'title' => 'Hi',
        'body' => 'Body.',
        'target_type' => 'department',
        'target_id' => $foreignDept->id,
    ])->assertSessionHasErrors('target_id');
});

test('department target accepts valid same-org department', function () {
    $org = Organization::factory()->create();
    actingAsRole('admin', $org);
    $dept = Department::factory()->create(['org_id' => $org->id]);

    $this->post('/announcements', [
        'title' => 'Team Update',
        'body' => 'Read me.',
        'target_type' => 'department',
        'target_id' => $dept->id,
    ])->assertRedirect();

    expect(Announcement::where('target_id', $dept->id)->where('title', 'Team Update')->exists())->toBeTrue();
});

// ── List page props ───────────────────────────────────────────────────────────

test('index returns announcements for current org', function () {
    $org = Organization::factory()->create();
    $otherOrg = Organization::factory()->create();
    $user = actingAsRole('admin', $org);
    $other = User::factory()->create(['org_id' => $otherOrg->id]);

    Announcement::factory()->create([
        'org_id' => $org->id,
        'author_id' => $user->id,
        'title' => 'My Org Post',
        'body' => 'Body.',
        'target_type' => 'all',
        'published_at' => now(),
    ]);

    Announcement::factory()->create([
        'org_id' => $otherOrg->id,
        'author_id' => $other->id,
        'title' => 'Other Org Post',
        'body' => 'Body.',
        'target_type' => 'all',
        'published_at' => now(),
    ]);

    $response = $this->get('/announcements');
    $props = $response->original->getData()['page']['props'];
    $titles = collect($props['announcements']['data'])->pluck('title');

    expect($titles)->toContain('My Org Post');
    expect($titles)->not->toContain('Other Org Post');
});

test('employees see only published announcements', function () {
    $org = Organization::factory()->create();
    $admin = User::factory()->create(['org_id' => $org->id]);
    $admin->assignRole('admin');

    Announcement::factory()->create([
        'org_id' => $org->id,
        'author_id' => $admin->id,
        'title' => 'Published',
        'body' => 'Body.',
        'target_type' => 'all',
        'published_at' => now()->subMinute(),
    ]);

    Announcement::factory()->create([
        'org_id' => $org->id,
        'author_id' => $admin->id,
        'title' => 'Draft',
        'body' => 'Body.',
        'target_type' => 'all',
        'published_at' => null,
    ]);

    actingAsRole('employee', $org);
    $response = $this->get('/announcements');
    $props = $response->original->getData()['page']['props'];
    $titles = collect($props['announcements']['data'])->pluck('title');

    expect($titles)->toContain('Published');
    expect($titles)->not->toContain('Draft');
});

test('admin sees drafts in the list', function () {
    $org = Organization::factory()->create();
    $user = actingAsRole('admin', $org);

    Announcement::factory()->create([
        'org_id' => $org->id,
        'author_id' => $user->id,
        'title' => 'Draft Post',
        'body' => 'Body.',
        'target_type' => 'all',
        'published_at' => null,
    ]);

    $response = $this->get('/announcements');
    $props = $response->original->getData()['page']['props'];
    $titles = collect($props['announcements']['data'])->pluck('title');

    expect($titles)->toContain('Draft Post');
});

// ── Show page ─────────────────────────────────────────────────────────────────

test('employee can view a published announcement', function () {
    $org = Organization::factory()->create();
    $employee = actingAsRole('employee', $org);
    $admin = User::factory()->create(['org_id' => $org->id]);

    $announcement = Announcement::factory()->create([
        'org_id' => $org->id,
        'author_id' => $admin->id,
        'title' => 'Hello Employees',
        'body' => 'Body content.',
        'target_type' => 'all',
        'published_at' => now()->subMinute(),
    ]);

    $this->get("/announcements/{$announcement->id}")->assertOk();
});

test('user cannot view announcement from another org', function () {
    $org = Organization::factory()->create();
    $otherOrg = Organization::factory()->create();
    actingAsRole('employee', $org);
    $otherAdmin = User::factory()->create(['org_id' => $otherOrg->id]);

    $foreignAnnouncement = Announcement::factory()->create([
        'org_id' => $otherOrg->id,
        'author_id' => $otherAdmin->id,
        'title' => 'Secret',
        'body' => 'Body.',
        'target_type' => 'all',
        'published_at' => now(),
    ]);

    // OrgScope hides cross-org records (404) — either 403 or 404 is correct behaviour
    $this->get("/announcements/{$foreignAnnouncement->id}")->assertStatus(404);
});

// ── Publish action ────────────────────────────────────────────────────────────

test('admin can publish a draft announcement', function () {
    $org = Organization::factory()->create();
    $user = actingAsRole('admin', $org);

    $announcement = Announcement::factory()->create([
        'org_id' => $org->id,
        'author_id' => $user->id,
        'title' => 'Draft',
        'body' => 'Body.',
        'target_type' => 'all',
        'published_at' => null,
    ]);

    $this->post("/announcements/{$announcement->id}/publish")->assertRedirect();

    expect($announcement->fresh()->published_at)->not->toBeNull();
});

// ── Delete action ─────────────────────────────────────────────────────────────

test('admin can soft-delete an announcement', function () {
    $org = Organization::factory()->create();
    $user = actingAsRole('admin', $org);

    $announcement = Announcement::factory()->create([
        'org_id' => $org->id,
        'author_id' => $user->id,
        'title' => 'Delete Me',
        'body' => 'Body.',
        'target_type' => 'all',
    ]);

    $this->delete("/announcements/{$announcement->id}")->assertRedirect('/announcements');

    expect(Announcement::find($announcement->id))->toBeNull();
    expect(Announcement::withTrashed()->find($announcement->id))->not->toBeNull();
});

test('employee cannot delete an announcement', function () {
    $org = Organization::factory()->create();
    actingAsRole('employee', $org);
    $admin = User::factory()->create(['org_id' => $org->id]);
    $admin->assignRole('admin');

    $announcement = Announcement::factory()->create([
        'org_id' => $org->id,
        'author_id' => $admin->id,
        'title' => 'Hands Off',
        'body' => 'Body.',
        'target_type' => 'all',
        'published_at' => now(),
    ]);

    $this->delete("/announcements/{$announcement->id}")->assertForbidden();
});

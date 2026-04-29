<?php

namespace Tests\Feature\Tenancy;

use App\Models\Holiday;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrgScopeTest extends TestCase
{
    use RefreshDatabase;

    private Organization $orgA;

    private Organization $orgB;

    private User $actorA;

    private User $actorB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orgA = Organization::create(['name' => 'Org A']);
        $this->orgB = Organization::create(['name' => 'Org B']);
        $this->actorA = User::factory()->create(['org_id' => $this->orgA->id]);
        $this->actorB = User::factory()->create(['org_id' => $this->orgB->id]);
    }

    public function test_authenticated_user_only_sees_their_orgs_records(): void
    {
        $this->actingAs($this->actorA);
        Holiday::create(['date' => '2026-01-07', 'name' => 'A-only']);

        $this->actingAs($this->actorB);
        Holiday::create(['date' => '2026-01-07', 'name' => 'B-only']);

        $this->actingAs($this->actorA);
        $this->assertSame(1, Holiday::count());
        $this->assertSame('A-only', Holiday::first()->name);

        $this->actingAs($this->actorB);
        $this->assertSame(1, Holiday::count());
        $this->assertSame('B-only', Holiday::first()->name);
    }

    public function test_creating_auto_sets_org_id_from_authenticated_user(): void
    {
        $this->actingAs($this->actorA);

        $holiday = Holiday::create([
            'date' => '2026-04-25',
            'name' => 'Sinai Liberation Day',
        ]);

        $this->assertSame($this->orgA->id, $holiday->fresh()->org_id);
    }

    public function test_explicit_org_id_is_not_overridden(): void
    {
        $this->actingAs($this->actorA);

        $holiday = Holiday::create([
            'org_id' => $this->orgB->id,
            'date' => '2026-04-25',
            'name' => 'Cross-tenant',
        ]);

        $this->assertSame($this->orgB->id, $holiday->fresh()->org_id);
    }

    public function test_no_authenticated_user_means_no_scope_applied(): void
    {
        Holiday::create(['org_id' => $this->orgA->id, 'date' => '2026-01-07', 'name' => 'A']);
        Holiday::create(['org_id' => $this->orgB->id, 'date' => '2026-01-07', 'name' => 'B']);

        $this->assertSame(2, Holiday::count());
    }

    public function test_without_org_scope_returns_all_records_across_tenants(): void
    {
        $this->actingAs($this->actorA);
        Holiday::create(['date' => '2026-01-07', 'name' => 'A-only']);

        $this->actingAs($this->actorB);
        Holiday::create(['date' => '2026-01-07', 'name' => 'B-only']);

        $this->actingAs($this->actorA);
        $bypassed = Holiday::withoutOrgScope('phase-3 super-admin migration check')->get();

        $this->assertCount(2, $bypassed);
        $names = $bypassed->pluck('name')->all();
        $this->assertContains('A-only', $names);
        $this->assertContains('B-only', $names);
    }

    public function test_without_org_scope_writes_audit_entry(): void
    {
        $this->actingAs($this->actorA);

        Holiday::withoutOrgScope('reason: monthly cross-tenant report')->get();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'org_scope_bypass',
            'user_id' => $this->actorA->id,
            'entity_type' => Holiday::class,
        ]);
    }

    public function test_query_includes_table_name_so_joins_dont_break(): void
    {
        $this->actingAs($this->actorA);

        $sql = Holiday::query()->toSql();
        $this->assertStringContainsString('"holidays"."org_id"', str_replace('`', '"', $sql));
    }
}

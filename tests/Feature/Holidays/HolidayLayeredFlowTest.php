<?php

namespace Tests\Feature\Holidays;

use App\Models\AuditLog;
use App\Models\Holiday;
use App\Models\Organization;
use App\Models\User;
use App\Permissions\RoleDefinitions;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F9 reference test — proves the full Controller → FormRequest → Service →
 * Repository → Model pipeline wires correctly, including:
 *   - permission middleware enforcement
 *   - FormRequest validation + uniqueness scoped per org
 *   - BelongsToOrg auto-fills org_id from the authenticated user
 *   - Auditable trait fires through service-level transactions
 *   - HolidayResource shape
 */
class HolidayLayeredFlowTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    private User $hr;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->org = Organization::factory()->create();
        $this->hr = User::factory()->create(['org_id' => $this->org->id]);
        $this->hr->assignRole(RoleDefinitions::ROLE_HR);
    }

    public function test_index_returns_paginated_resource_collection(): void
    {
        $this->actingAs($this->hr);

        Holiday::create(['date' => '2026-04-25', 'name' => 'Sinai Liberation Day']);
        Holiday::create(['date' => '2026-05-01', 'name' => 'Labor Day']);

        $response = $this->getJson('/admin/holidays');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'org_id', 'name', 'date', 'is_recurring', 'is_make_up', 'version']],
                'meta' => ['current_page', 'per_page', 'total'],
            ]);
    }

    public function test_store_creates_holiday_through_all_layers(): void
    {
        $this->actingAs($this->hr);

        $response = $this->postJson('/admin/holidays', [
            'name' => 'Coptic Christmas',
            'date' => '2026-01-07',
            'is_make_up' => false,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Coptic Christmas')
            ->assertJsonPath('data.date', '2026-01-07')
            ->assertJsonPath('data.org_id', $this->org->id);

        $this->assertDatabaseHas('holidays', [
            'org_id' => $this->org->id,
            'name' => 'Coptic Christmas',
            'date' => '2026-01-07',
        ]);

        // Service used transaction() → Auditable trait fired
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'entity_type' => Holiday::class,
            'org_id' => $this->org->id,
            'user_id' => $this->hr->id,
        ]);
    }

    public function test_store_rejects_missing_required_fields(): void
    {
        $this->actingAs($this->hr);

        $this->postJson('/admin/holidays', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'date']);
    }

    public function test_store_rejects_duplicate_date_in_same_org(): void
    {
        $this->actingAs($this->hr);
        Holiday::create(['date' => '2026-01-07', 'name' => 'Coptic Christmas']);

        $this->postJson('/admin/holidays', [
            'name' => 'Duplicate',
            'date' => '2026-01-07',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    public function test_store_allows_same_date_in_different_orgs(): void
    {
        $orgB = Organization::factory()->create();
        $hrB = User::factory()->create(['org_id' => $orgB->id]);
        $hrB->assignRole(RoleDefinitions::ROLE_HR);

        $this->actingAs($this->hr)
            ->postJson('/admin/holidays', ['name' => 'Coptic Christmas', 'date' => '2026-01-07'])
            ->assertCreated();

        $this->actingAs($hrB)
            ->postJson('/admin/holidays', ['name' => 'Coptic Christmas', 'date' => '2026-01-07'])
            ->assertCreated();
    }

    public function test_update_changes_holiday_and_records_diff_in_audit(): void
    {
        $this->actingAs($this->hr);
        $holiday = Holiday::create(['date' => '2026-01-07', 'name' => 'Coptic Christmas']);

        $this->patchJson("/admin/holidays/{$holiday->id}", [
            'name' => 'Coptic Christmas (renamed)',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Coptic Christmas (renamed)');

        $audit = AuditLog::where('action', 'updated')
            ->where('entity_type', Holiday::class)
            ->where('entity_id', (string) $holiday->id)
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame('Coptic Christmas', $audit->changes['before']['name']);
        $this->assertSame('Coptic Christmas (renamed)', $audit->changes['after']['name']);
    }

    public function test_destroy_soft_deletes_and_audits(): void
    {
        $this->actingAs($this->hr);
        $holiday = Holiday::create(['date' => '2026-01-07', 'name' => 'Coptic Christmas']);

        $this->deleteJson("/admin/holidays/{$holiday->id}")->assertNoContent();

        $this->assertSoftDeleted('holidays', ['id' => $holiday->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'deleted',
            'entity_type' => Holiday::class,
            'entity_id' => (string) $holiday->id,
        ]);
    }

    public function test_show_returns_single_resource(): void
    {
        $this->actingAs($this->hr);
        $holiday = Holiday::create(['date' => '2026-04-25', 'name' => 'Sinai Liberation Day']);

        $this->getJson("/admin/holidays/{$holiday->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $holiday->id)
            ->assertJsonPath('data.name', 'Sinai Liberation Day');
    }

    public function test_employee_without_permission_is_blocked(): void
    {
        $employee = User::factory()->create(['org_id' => $this->org->id]);
        $employee->assignRole(RoleDefinitions::ROLE_EMPLOYEE);

        $this->actingAs($employee)
            ->postJson('/admin/holidays', ['name' => 'X', 'date' => '2026-01-07'])
            ->assertStatus(403);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->postJson('/admin/holidays', ['name' => 'X', 'date' => '2026-01-07'])
            ->assertStatus(401);
    }
}

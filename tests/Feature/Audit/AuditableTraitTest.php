<?php

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\Holiday;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuditableTraitTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;

    protected function setUp(): void
    {
        parent::setUp();
        $this->org = Organization::create(['name' => 'Test Org']);
    }

    public function test_create_writes_audit_entry_with_after_payload(): void
    {
        $holiday = Holiday::create([
            'org_id' => $this->org->id,
            'date' => '2026-01-07',
            'name' => 'Coptic Christmas',
            'is_make_up' => false,
        ]);

        $audit = AuditLog::where('entity_type', Holiday::class)
            ->where('entity_id', (string) $holiday->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame($this->org->id, $audit->org_id);
        $this->assertArrayHasKey('after', $audit->changes);
        $this->assertSame('Coptic Christmas', $audit->changes['after']['name']);
    }

    public function test_update_writes_audit_entry_with_before_and_after_diff(): void
    {
        $holiday = Holiday::create([
            'org_id' => $this->org->id,
            'date' => '2026-01-07',
            'name' => 'Coptic Christmas',
            'is_make_up' => false,
        ]);

        $holiday->update(['name' => 'Coptic Christmas (renamed)']);

        $audit = AuditLog::where('entity_type', Holiday::class)
            ->where('entity_id', (string) $holiday->id)
            ->where('action', 'updated')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame('Coptic Christmas', $audit->changes['before']['name']);
        $this->assertSame('Coptic Christmas (renamed)', $audit->changes['after']['name']);
        $this->assertCount(1, $audit->changes['after']);
    }

    public function test_delete_writes_audit_entry(): void
    {
        $holiday = Holiday::create([
            'org_id' => $this->org->id,
            'date' => '2026-01-07',
            'name' => 'Coptic Christmas',
        ]);

        $holiday->delete();

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => Holiday::class,
            'entity_id' => (string) $holiday->id,
            'action' => 'deleted',
        ]);
    }

    public function test_restore_writes_audit_entry(): void
    {
        $holiday = Holiday::create([
            'org_id' => $this->org->id,
            'date' => '2026-01-07',
            'name' => 'Coptic Christmas',
        ]);
        $holiday->delete();
        $holiday->restore();

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => Holiday::class,
            'entity_id' => (string) $holiday->id,
            'action' => 'restored',
        ]);
    }

    public function test_force_delete_writes_force_deleted_audit_entry(): void
    {
        $holiday = Holiday::create([
            'org_id' => $this->org->id,
            'date' => '2026-01-07',
            'name' => 'Coptic Christmas',
        ]);
        $id = $holiday->id;
        $holiday->forceDelete();

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => Holiday::class,
            'entity_id' => (string) $id,
            'action' => 'force_deleted',
        ]);
    }

    public function test_no_op_save_does_not_write_audit(): void
    {
        $holiday = Holiday::create([
            'org_id' => $this->org->id,
            'date' => '2026-01-07',
            'name' => 'Coptic Christmas',
        ]);

        $countBefore = AuditLog::count();
        $holiday->save();
        $this->assertSame($countBefore, AuditLog::count());
    }

    public function test_audit_entry_captures_actor_user_and_request_metadata(): void
    {
        $actor = User::factory()->create(['org_id' => $this->org->id]);

        Route::middleware(['web', 'auth'])
            ->post('/_test_audit_create', function () use ($actor) {
                Holiday::create([
                    'org_id' => $actor->org_id,
                    'date' => '2026-04-25',
                    'name' => 'Sinai Liberation Day',
                ]);

                return 'ok';
            });

        $response = $this->actingAs($actor)
            ->withHeaders(['User-Agent' => 'AuditBrowser/1.0'])
            ->withServerVariables(['REMOTE_ADDR' => '10.0.0.5'])
            ->post('/_test_audit_create');

        $response->assertOk();

        $audit = AuditLog::where('action', 'created')
            ->where('entity_type', Holiday::class)
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame($actor->id, $audit->user_id);
        $this->assertSame('10.0.0.5', $audit->ip_address);
        $this->assertStringContainsString('AuditBrowser', $audit->user_agent);
    }

    public function test_excluded_attributes_are_not_persisted_to_audit_changes(): void
    {
        $holiday = Holiday::create([
            'org_id' => $this->org->id,
            'date' => '2026-01-07',
            'name' => 'Coptic Christmas',
        ]);

        $audit = AuditLog::where('entity_type', Holiday::class)
            ->where('action', 'created')
            ->first();

        $this->assertArrayNotHasKey('created_at', $audit->changes['after']);
        $this->assertArrayNotHasKey('updated_at', $audit->changes['after']);
    }
}

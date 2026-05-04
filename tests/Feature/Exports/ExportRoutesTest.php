<?php

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->org = Organization::factory()->create();
    $this->hr = User::factory()->create(['org_id' => $this->org->id]);
    $this->hr->assignRole('hr');
    $this->employee = User::factory()->create(['org_id' => $this->org->id]);
    $this->employee->assignRole('employee');
});

describe('exports — auth gating', function () {
    test('it blocks employees from each export endpoint', function () {
        $this->actingAs($this->employee)->get('/exports/employees')->assertForbidden();
        $this->actingAs($this->employee)->get('/exports/assets')->assertForbidden();
        $this->actingAs($this->employee)->get('/exports/documents')->assertForbidden();
    });

    test('it redirects guests to login on every export endpoint', function () {
        $this->get('/exports/employees')->assertRedirect('/login');
        $this->get('/exports/assets')->assertRedirect('/login');
        $this->get('/exports/documents')->assertRedirect('/login');
    });
});

describe('exports — employees', function () {
    test('it lets HR download an XLSX of employees in their org', function () {
        Employee::factory()->count(3)->create(['org_id' => $this->org->id]);

        $resp = $this->actingAs($this->hr)->get('/exports/employees');

        $resp->assertOk();
        expect($resp->headers->get('content-disposition'))
            ->toContain('attachment')
            ->toContain('.xlsx');
    });

    test('it lets HR download a CSV when format=csv', function () {
        Employee::factory()->count(2)->create(['org_id' => $this->org->id]);

        $resp = $this->actingAs($this->hr)->get('/exports/employees?format=csv');

        $resp->assertOk();
        expect($resp->headers->get('content-disposition'))
            ->toContain('attachment')
            ->toContain('.csv');
    });

    test('it scopes the export to the current org only', function () {
        $otherOrg = Organization::factory()->create();
        Employee::factory()->count(2)->create([
            'org_id' => $this->org->id,
            'first_name' => 'Inside',
        ]);
        Employee::factory()->count(3)->create([
            'org_id' => $otherOrg->id,
            'first_name' => 'Outside',
        ]);

        $resp = $this->actingAs($this->hr)->get('/exports/employees?format=csv');
        $resp->assertOk();

        $body = file_get_contents($resp->baseResponse->getFile()->getPathname());
        expect($body)->toContain('Inside');
        expect($body)->not->toContain('Outside');
    });
});

describe('exports — assets', function () {
    test('it lets HR download an XLSX of assets in their org', function () {
        $cat = AssetCategory::create([
            'org_id' => $this->org->id,
            'name' => 'Laptop',
            'is_active' => true,
        ]);
        Asset::create([
            'org_id' => $this->org->id,
            'asset_category_id' => $cat->id,
            'name' => 'Dell XPS 15',
            'serial_number' => 'LAP-1',
            'value_piasters' => 5_000_000,
            'acquired_date' => now()->subMonths(6)->toDateString(),
            'condition_at_acquisition' => 'new',
            'current_status' => Asset::STATUS_IN_POOL,
        ]);

        $resp = $this->actingAs($this->hr)->get('/exports/assets');

        $resp->assertOk();
        expect($resp->headers->get('content-disposition'))
            ->toContain('attachment')
            ->toContain('.xlsx');
    });
});

describe('exports — documents', function () {
    test('it lets HR download an XLSX of employee documents in their org', function () {
        $type = DocumentType::create([
            'org_id' => $this->org->id,
            'name' => 'National ID',
            'is_required' => true,
            'applies_to' => 'all',
            'is_active' => true,
        ]);
        $emp = Employee::factory()->create(['org_id' => $this->org->id]);
        EmployeeDocument::create([
            'org_id' => $this->org->id,
            'employee_id' => $emp->id,
            'document_type_id' => $type->id,
            'file_path' => 'docs/nid-1.pdf',
            'original_filename' => 'nid.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'uploaded_by_user_id' => $this->hr->id,
        ]);

        $resp = $this->actingAs($this->hr)->get('/exports/documents');

        $resp->assertOk();
        expect($resp->headers->get('content-disposition'))
            ->toContain('attachment')
            ->toContain('.xlsx');
    });
});

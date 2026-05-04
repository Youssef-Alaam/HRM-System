<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Organization;
use App\Models\Position;
use App\Support\PositionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Validates the one-off backfill that rewrote pre-2026-04-30 YZH-{org}-NNNN
 * employee codes into the EMP-{type}{NNNN} sticky format. RefreshDatabase
 * already ran the migration against an empty table, so we re-invoke its
 * up() against a hand-rolled YZH-flavored fixture state.
 */

it('rewrites every employees employee_code into EMP-{type}{NNNN}, ordered by hiring date per type bucket', function () {
    $org = Organization::factory()->create();
    $office = Office::factory()->create(['org_id' => $org->id]);
    $dept = Department::factory()->create(['org_id' => $org->id]);

    $engPosition = Position::factory()->create([
        'org_id' => $org->id,
        'department_id' => $dept->id,
        'type_code' => PositionType::ENGINEERING,
    ]);
    $salesPosition = Position::factory()->create([
        'org_id' => $org->id,
        'department_id' => $dept->id,
        'type_code' => PositionType::SALES,
    ]);

    // Hand-roll old-format rows in the order Walid had them in the seed.
    // Mix engineers and salespeople so we can verify per-bucket sequencing.
    // The factory's afterMaking hook would force EMP- codes onto these,
    // so we work around it by using DB::table inserts after building the
    // models with the factory (only to grab a valid attribute set).
    $orderedFixtures = [
        ['code' => 'YZH-1-0001', 'position_id' => $engPosition->id,   'hiring_date' => '2024-01-15'],
        ['code' => 'YZH-1-0002', 'position_id' => $salesPosition->id, 'hiring_date' => '2024-02-01'],
        ['code' => 'YZH-1-0003', 'position_id' => $engPosition->id,   'hiring_date' => '2024-03-10'],
        ['code' => 'YZH-1-0004', 'position_id' => $salesPosition->id, 'hiring_date' => '2024-04-05'],
        ['code' => 'YZH-1-0005', 'position_id' => $engPosition->id,   'hiring_date' => '2024-05-20'],
    ];

    foreach ($orderedFixtures as $fix) {
        $employee = Employee::factory()->create([
            'org_id' => $org->id,
            'office_id' => $office->id,
            'department_id' => $dept->id,
            'position_id' => $fix['position_id'],
            'hiring_date' => $fix['hiring_date'],
            // afterMaking will overwrite this — we forcibly reset it
            // below to mimic a pre-migration YZH-{org}-NNNN database.
        ]);
        DB::table('employees')
            ->where('id', $employee->id)
            ->update(['employee_code' => $fix['code']]);
    }

    // Sanity: data is in the pre-migration state.
    $oldCodes = DB::table('employees')->orderBy('hiring_date')->pluck('employee_code')->all();
    expect($oldCodes)->toBe(['YZH-1-0001', 'YZH-1-0002', 'YZH-1-0003', 'YZH-1-0004', 'YZH-1-0005']);

    // Re-run the backfill migration's up() against the doctored state.
    $migration = require database_path('migrations/2026_04_30_010001_backfill_emp_codes_on_employees_table.php');
    $migration->up();

    $byHire = DB::table('employees')
        ->orderBy('hiring_date')
        ->pluck('employee_code')
        ->all();

    // Engineering bucket (type 1): 1st = 0001 (Jan), 2nd = 0002 (Mar), 3rd = 0003 (May)
    // Sales bucket (type 2): 1st = 0001 (Feb), 2nd = 0002 (Apr)
    expect($byHire)->toBe([
        'EMP-10001', // engineer #1, Jan 15
        'EMP-20001', // sales #1,    Feb 01
        'EMP-10002', // engineer #2, Mar 10
        'EMP-20002', // sales #2,    Apr 05
        'EMP-10003', // engineer #3, May 20
    ]);
});

it('preserves stickiness when re-run on already-backfilled rows', function () {
    // The migration is one-off but conceptually idempotent — re-running
    // it against EMP-XXXXX rows should produce the same codes (each row
    // claims the same slot in tenure order it already had).
    $org = Organization::factory()->create();
    $office = Office::factory()->create(['org_id' => $org->id]);
    $dept = Department::factory()->create(['org_id' => $org->id]);

    $position = Position::factory()->create([
        'org_id' => $org->id,
        'department_id' => $dept->id,
        'type_code' => PositionType::ENGINEERING,
    ]);

    foreach (['2024-01-01', '2024-02-01', '2024-03-01'] as $i => $date) {
        $employee = Employee::factory()->create([
            'org_id' => $org->id,
            'office_id' => $office->id,
            'department_id' => $dept->id,
            'position_id' => $position->id,
            'hiring_date' => $date,
        ]);
        DB::table('employees')
            ->where('id', $employee->id)
            ->update(['employee_code' => sprintf('EMP-1%04d', $i + 1)]);
    }

    $before = DB::table('employees')->orderBy('hiring_date')->pluck('employee_code')->all();

    $migration = require database_path('migrations/2026_04_30_010001_backfill_emp_codes_on_employees_table.php');
    $migration->up();

    $after = DB::table('employees')->orderBy('hiring_date')->pluck('employee_code')->all();

    expect($after)->toBe($before);
});

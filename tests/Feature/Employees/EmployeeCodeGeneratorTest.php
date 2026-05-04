<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Organization;
use App\Models\Position;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Support\PositionType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Coverage for the EMP-{type}{NNNN} sticky employee_code format locked
 * with Walid on 2026-04-30. The format encodes the position's type_code
 * (0-9) as the leading digit and a tenure-ordered four-digit sequence
 * within that type bucket.
 */

function makePosition(int $orgId, int $typeCode, ?int $departmentId = null): Position
{
    $departmentId ??= Department::factory()->create(['org_id' => $orgId])->id;

    return Position::factory()->create([
        'org_id' => $orgId,
        'department_id' => $departmentId,
        'type_code' => $typeCode,
    ]);
}

function buildEmployeeRow(int $orgId, int $positionId, ?string $hiringDate = null, ?string $code = null): Employee
{
    static $emailCounter = 0;
    $emailCounter++;

    $office = Office::factory()->create(['org_id' => $orgId]);

    $attrs = [
        'org_id' => $orgId,
        'position_id' => $positionId,
        'office_id' => $office->id,
        'email' => "fixture+{$emailCounter}@example.test",
        'national_id' => str_pad((string) $emailCounter, 14, '2', STR_PAD_LEFT),
        'hiring_date' => $hiringDate,
    ];

    if ($code !== null) {
        $attrs['employee_code'] = $code;
    }

    return Employee::factory()->create($attrs);
}

describe('PositionType::inferFromTitle', function () {
    it('maps engineering titles to ENGINEERING', function () {
        expect(PositionType::inferFromTitle('Software Engineer'))->toBe(PositionType::ENGINEERING);
        expect(PositionType::inferFromTitle('DevOps Lead'))->toBe(PositionType::ENGINEERING);
        expect(PositionType::inferFromTitle('Senior QA'))->toBe(PositionType::ENGINEERING);
    });

    it('maps sales titles to SALES', function () {
        expect(PositionType::inferFromTitle('Sales Representative'))->toBe(PositionType::SALES);
        expect(PositionType::inferFromTitle('Account Manager'))->toBe(PositionType::SALES);
    });

    it('maps HR titles to HR', function () {
        expect(PositionType::inferFromTitle('HR Manager'))->toBe(PositionType::HR);
        expect(PositionType::inferFromTitle('Talent Acquisition Lead'))->toBe(PositionType::HR);
    });

    it('maps finance titles to FINANCE', function () {
        expect(PositionType::inferFromTitle('Finance Officer'))->toBe(PositionType::FINANCE);
        expect(PositionType::inferFromTitle('Senior Accountant'))->toBe(PositionType::FINANCE);
    });

    it('maps executive C-suite titles to EXECUTIVE before any other bucket', function () {
        expect(PositionType::inferFromTitle('Chief Technology Officer'))->toBe(PositionType::EXECUTIVE);
        expect(PositionType::inferFromTitle('VP Sales'))->toBe(PositionType::EXECUTIVE);
        expect(PositionType::inferFromTitle('CEO'))->toBe(PositionType::EXECUTIVE);
    });

    it('falls through to OTHER on unknown titles', function () {
        expect(PositionType::inferFromTitle('Mystery Wizard'))->toBe(PositionType::OTHER);
        expect(PositionType::inferFromTitle(''))->toBe(PositionType::OTHER);
    });
});

describe('EmployeeRepository::generateEmployeeCode', function () {
    it('issues EMP-{type}0001 for the first hire in an empty bucket', function () {
        $org = Organization::factory()->create();
        $position = makePosition($org->id, PositionType::ENGINEERING);

        $code = app(EmployeeRepositoryInterface::class)
            ->generateEmployeeCode($org->id, $position->id);

        expect($code)->toBe('EMP-10001');
    });

    it('increments the tenure suffix per existing hire in the same type bucket', function () {
        $org = Organization::factory()->create();
        $position = makePosition($org->id, PositionType::SALES);

        buildEmployeeRow($org->id, $position->id, hiringDate: '2024-01-01');
        buildEmployeeRow($org->id, $position->id, hiringDate: '2024-02-01');

        $code = app(EmployeeRepositoryInterface::class)
            ->generateEmployeeCode($org->id, $position->id);

        expect($code)->toBe('EMP-20003');
    });

    it('keeps separate sequences per type bucket', function () {
        $org = Organization::factory()->create();
        $eng = makePosition($org->id, PositionType::ENGINEERING);
        $sales = makePosition($org->id, PositionType::SALES);

        buildEmployeeRow($org->id, $eng->id, hiringDate: '2024-01-01');
        buildEmployeeRow($org->id, $eng->id, hiringDate: '2024-02-01');
        buildEmployeeRow($org->id, $sales->id, hiringDate: '2024-03-01');

        $repo = app(EmployeeRepositoryInterface::class);
        expect($repo->generateEmployeeCode($org->id, $eng->id))->toBe('EMP-10003');
        expect($repo->generateEmployeeCode($org->id, $sales->id))->toBe('EMP-20002');
    });

    it('does not reuse a soft-deleted employees slot — codes stay sticky', function () {
        $org = Organization::factory()->create();
        $position = makePosition($org->id, PositionType::ENGINEERING);

        $first = buildEmployeeRow($org->id, $position->id, hiringDate: '2024-01-01', code: 'EMP-10001');
        buildEmployeeRow($org->id, $position->id, hiringDate: '2024-02-01', code: 'EMP-10002');

        $first->delete();

        $code = app(EmployeeRepositoryInterface::class)
            ->generateEmployeeCode($org->id, $position->id);

        // 0003, NOT 0002 — soft-deleted 0001 is preserved as a tombstone slot.
        expect($code)->toBe('EMP-10003');
    });

    it('throws DomainException when a type bucket overflows 9999 entries', function () {
        $org = Organization::factory()->create();
        $position = makePosition($org->id, PositionType::OTHER);

        buildEmployeeRow($org->id, $position->id, hiringDate: '2024-01-01', code: 'EMP-99999');

        app(EmployeeRepositoryInterface::class)
            ->generateEmployeeCode($org->id, $position->id);
    })->throws(DomainException::class, 'EMP-9 bucket has overflowed 9999 entries');

    it('isolates sequences across orgs (multi-tenancy)', function () {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $posA = makePosition($orgA->id, PositionType::ENGINEERING);
        $posB = makePosition($orgB->id, PositionType::ENGINEERING);

        buildEmployeeRow($orgA->id, $posA->id, hiringDate: '2024-01-01', code: 'EMP-10001');
        buildEmployeeRow($orgA->id, $posA->id, hiringDate: '2024-02-01', code: 'EMP-10002');

        // Org B starts fresh — its bucket is empty.
        $code = app(EmployeeRepositoryInterface::class)
            ->generateEmployeeCode($orgB->id, $posB->id);

        expect($code)->toBe('EMP-10001');
    });
});

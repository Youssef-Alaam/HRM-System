<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use App\Permissions\RoleDefinitions;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Str;

/**
 * Layer-3 service for the Employees feature. Wraps create/update/delete in
 * a transaction (BaseService::transaction). Auditable model trait writes
 * before/after diffs to audit_logs for every write.
 *
 * Tier-aware update logic lives in EmployeeFormRequest::authorize() — this
 * service trusts validated input.
 *
 * Decision 18 (universal law-minimum leave balances on Day 1) — initial
 * balance values are seeded inline here as a stub; Feature 6 (My Leave)
 * lifts this into a dedicated LeaveService::initializeBalances() method
 * with proper Egyptian Labor Law tenure-based calculation.
 */
class EmployeeService extends BaseService
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $repo,
    ) {}

    /**
     * @param  array<string, mixed>  $data  Validated payload from StoreEmployeeRequest
     */
    public function create(array $data): Employee
    {
        return $this->transaction(function () use ($data) {
            $orgId = auth()->user()->org_id;

            // 1. Create the User account first so we can link it.
            $user = User::create([
                'org_id' => $orgId,
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
                // Force-reset on first login — store a random hash for now.
                // Feature 14 (welcome flow) will replace this with an
                // invitation-link mechanism that emails the new user.
                'password' => bcrypt(Str::random(32)),
            ]);
            $user->assignRole(RoleDefinitions::ROLE_EMPLOYEE);

            // 2. Create the Employee row, linked. Code format = EMP-XXXXX
            // where the first digit is the position's type_code (0-9) and
            // the remaining four are the tenure sequence within that bucket.
            $employeeCode = $this->repo->generateEmployeeCode($orgId, (int) $data['position_id']);
            $employee = $this->repo->create(array_merge($data, [
                'org_id' => $orgId,
                'user_id' => $user->id,
                'employee_code' => $employeeCode,
                'employment_status' => 'active',
                // Per Decision 18 — universal law-minimum, Day 1.
                'annual_leave_balance_days' => 15.0,
                'sick_leave_balance_days' => 0.0,
                'casual_leave_balance_days' => 7.0,
                'permissions_balance_minutes' => 0,
            ]));

            // 3. Close the loop: user.employee_id points back at the new row.
            $user->forceFill(['employee_id' => $employee->id])->save();

            return $employee;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Employee
    {
        return $this->transaction(function () use ($id, $data) {
            $employee = $this->repo->findOrFail($id);

            return $this->repo->update($employee, $data);
        });
    }

    public function softDelete(int $id, string $reason): void
    {
        $this->transaction(function () use ($id, $reason) {
            $employee = $this->repo->findOrFail($id);
            $this->repo->softDelete($employee, $reason);
        });
    }
}

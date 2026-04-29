<?php

namespace App\Permissions;

/**
 * Single source of truth for the permission catalog and role-to-permission mapping.
 * Mirrors PRD §3.2. Update both in lockstep.
 */
class RoleDefinitions
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_HR = 'hr';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_EMPLOYEE = 'employee';

    /**
     * @return array<int, string> All four role names.
     */
    public static function roles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_HR,
            self::ROLE_MANAGER,
            self::ROLE_EMPLOYEE,
        ];
    }

    /**
     * Full catalog of permission strings, grouped by domain for readability.
     * Flat list returned. Domain grouping is documentation-only.
     *
     * @return array<int, string>
     */
    public static function allPermissions(): array
    {
        return [
            // employees
            'employees.view.own',
            'employees.view.team',
            'employees.view.any',
            'employees.edit.own.tier1',
            'employees.edit.own.tier2',
            'employees.create',
            'employees.edit.any',
            'employees.delete',
            'employees.restore',
            'employees.terminate',
            'employees.confirm_deemed_resignation',
            'employees.override_foreign_quota',

            // attendance
            'attendance.checkin.own',
            'attendance.view.own',
            'attendance.view.team',
            'attendance.view.any',
            'attendance.edit.any',
            'attendance.correct.own',
            'attendance.correct.team',
            'attendance.assign_shifts.team',
            'attendance.assign_shifts.any',

            // leave
            'leave.request.own',
            'leave.view.own',
            'leave.view.team',
            'leave.view.any',
            'leave.approve.team',
            'leave.approve.final',
            'leave.edit.any',

            // requests (non-leave)
            'requests.create.own',
            'requests.view.own',
            'requests.view.team',
            'requests.view.any',
            'requests.approve.team',
            'requests.approve.final',

            // payroll
            'payroll.run',
            'payroll.lock',
            'payroll.mark_paid',
            'payroll.payslip.edit',
            'payroll.payslip.view.own',
            'payroll.payslip.view.team',
            'payroll.payslip.view.any',

            // org
            'org.departments.manage',
            'org.positions.manage',
            'org.offices.manage',
            'org.holidays.manage',
            'org.holidays.bulk_apply',

            // users & access
            'users.create',
            'users.disable',
            'users.assign_roles',
            'users.assign_permissions',
            'users.unlock',

            // chat
            'chat.send',
            'chat.view.team',
            'chat.view.any',

            // availability
            'availability.view.own',
            'availability.view.team',
            'availability.view.any',
            'availability.set.own',
            'availability.set.team',

            // announcements
            'announcements.view',
            'announcements.create',

            // audit & settings
            'audit.view',
            'settings.edit',

            // reports & exports
            'reports.run.own',
            'reports.run.team',
            'reports.run.any',
            'exports.own',
            'exports.team',
            'exports.any',
            'exports.pdpl_self_service',

            // security
            'security.step_up.required',
        ];
    }

    /**
     * Default permissions per role. Admin gets everything.
     *
     * @return array<string, array<int, string>>
     */
    public static function rolePermissions(): array
    {
        $employee = [
            'employees.view.own',
            'employees.edit.own.tier1',
            'attendance.checkin.own',
            'attendance.view.own',
            'attendance.correct.own',
            'leave.request.own',
            'leave.view.own',
            'requests.create.own',
            'requests.view.own',
            'payroll.payslip.view.own',
            'chat.send',
            'availability.view.own',
            'availability.set.own',
            'announcements.view',
            'reports.run.own',
            'exports.own',
            'exports.pdpl_self_service',
        ];

        $manager = array_merge($employee, [
            'employees.view.team',
            'attendance.view.team',
            'attendance.correct.team',
            'attendance.assign_shifts.team',
            'leave.view.team',
            'leave.approve.team',
            'requests.view.team',
            'requests.approve.team',
            'chat.view.team',
            'availability.view.team',
            'availability.set.team',
            'reports.run.team',
            'exports.team',
        ]);

        $hr = array_merge($manager, [
            'employees.view.any',
            'employees.edit.own.tier2',
            'employees.create',
            'employees.edit.any',
            'employees.delete',
            'employees.terminate',
            'employees.confirm_deemed_resignation',
            'employees.override_foreign_quota',
            'attendance.view.any',
            'attendance.edit.any',
            'attendance.assign_shifts.any',
            'leave.view.any',
            'leave.approve.final',
            'leave.edit.any',
            'requests.view.any',
            'requests.approve.final',
            'payroll.run',
            'payroll.lock',
            'payroll.mark_paid',
            'payroll.payslip.edit',
            'payroll.payslip.view.team',
            'payroll.payslip.view.any',
            'org.departments.manage',
            'org.positions.manage',
            'org.offices.manage',
            'org.holidays.manage',
            'org.holidays.bulk_apply',
            'users.unlock',
            'chat.view.any',
            'availability.view.any',
            'announcements.create',
            'reports.run.any',
            'exports.any',
            'security.step_up.required',
        ]);

        return [
            self::ROLE_ADMIN => self::allPermissions(),
            self::ROLE_HR => array_values(array_unique($hr)),
            self::ROLE_MANAGER => array_values(array_unique($manager)),
            self::ROLE_EMPLOYEE => array_values(array_unique($employee)),
        ];
    }
}

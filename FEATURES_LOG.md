# YZH-HR — Features Log

> Append-only record of every Feature Phase task. Walid's bulk-review artifact:
> reads top-to-bottom to walk every shipped feature with what it does, where to
> find it, key tests, edge cases, and known limitations. Each entry follows the
> same template.
>
> See [TASK_MANAGER.md](TASK_MANAGER.md) for the full feature checklist and
> CLAUDE.md "Build flow" for the per-feature internal quality gate that every
> entry below has passed.

---

## Feature 1 — Dashboard ✅

**Shipped:** 2026-04-30
**Spec:** [specs/feature-1-dashboard.md](specs/feature-1-dashboard.md)
**Tests added:** 11 (in `tests/Feature/Dashboard/DashboardTest.php`)
**Total tests after:** 135 passing, 646 assertions

### What it does

Role-aware dashboard at `GET /dashboard`. Each of the four roles
(Employee / Manager / HR / Admin) sees only the widgets their permissions
entitle them to:

- **Identity** — name + email + role, every user sees this
- **Leave balance** — annual / sick / casual days, anyone with an Employee record
- **Headcount** — active employee total, Manager-and-up (`employees.view.team`)
- **Department breakdown** — active employees per department, HR-and-up (`employees.view.any`)
- **System metrics** — user count + audit-log size + holidays count, Admin only
- **Recent logins** — last 5 successful login events, Admin only
- **Drawing index** — read-only roadmap of upcoming modules (Attendance, Leave, etc.) — same for everyone, with status badges

The `widgets` payload is server-composed by `DashboardService::getDataForRole(User)`. Frontend is data-presence-driven — no client-side role checking, no leaking of admin payloads to non-admins.

### Where to find it

- Route: `GET /dashboard`
- Controller: [`app/Http/Controllers/DashboardController.php`](app/Http/Controllers/DashboardController.php)
- Service: [`app/Services/DashboardService.php`](app/Services/DashboardService.php)
- Repository: [`app/Repositories/DashboardRepository.php`](app/Repositories/DashboardRepository.php) implements [`DashboardRepositoryInterface`](app/Repositories/Contracts/DashboardRepositoryInterface.php), bound in [`AppServiceProvider`](app/Providers/AppServiceProvider.php)
- Frontend: [`resources/js/Pages/Dashboard.tsx`](resources/js/Pages/Dashboard.tsx)

### How to verify (per role)

1. Run `php artisan migrate:fresh --seed` then sign in as one of the seeded test users:
   - `admin@yzh.test` / `password` — should see every widget including system metrics + recent logins
   - `hr@yzh.test` / `password` — should see headcount + department breakdown but NOT system metrics or recent logins
   - `manager@yzh.test` / `password` — should see headcount but NOT department breakdown
   - `employee@yzh.test` / `password` — should see identity + leave balance only (when linked to an Employee record)

2. Open DevTools → Network. Watch for a request to `/dashboard?only=widgets` every 60 seconds (Headcount polling per Decision 27). Switch tabs — polling pauses; switch back — polling resumes.

3. `?loading=1` on `/dashboard` forces the skeleton state across all widgets (carryover from F12.5 demo hook).

### Key tests

- `it renders for an authenticated employee with employee-tier widgets only`
- `it shows the headcount widget for HR users`
- `it shows the headcount widget for managers`
- `it shows system metrics + recent logins to admins only`
- `it does not leak admin widgets to HR users`
- `it returns headcount scoped to the user org only (multi-tenancy)` ← OrgScope verified
- `it returns 0 headcount for a fresh org with no employees`
- `it exposes leave balance to an employee with an employee record`
- `it omits leave balance when user has no employee record`
- `it returns the department breakdown for HR with counts per department`
- `it redirects guests to login`

### Edge cases handled

- User with no employee record (HR creates user but employee row is null) — leave balance widget omitted, no error
- Empty department list (fresh tenant) — department breakdown shows "No departments yet" empty state
- Empty audit log (fresh tenant) — recent logins shows "No login events recorded yet" empty state
- 0 active employees — headcount shows "0" without breaking
- User has no role assigned — defaults to employee variant (least privilege)

### Compliance citations

None. Read-only aggregation. No Egyptian Labor Law calculations.

### Known limitations / deferred

- **Headcount breakdown segments** (active-on-time / late / absent / on-leave / day-off per Decision 27) — currently only `total` is real. The 5-segment breakdown waits for Attendance (Feature 5) and Leave (Feature 6) to ship the underlying data.
- **Drill-down modal** on segment click (Decision 27) — deferred until segments are real.
- **Today's schedule card** (employee + manager) — placeholder copy in the Drawing index. Real content arrives with Feature 4 (My Schedule).
- **Pending requests count** (employee) — not surfaced yet. Lands with Feature 6 (My Leave).
- **Pending approvals count** (manager) — not surfaced yet. Lands with Feature 7 (Approvals).
- **Coming soon: payslip placeholder** — covered by the Drawing index entry. Real content with Feature 15 (Payroll, deferred per Walid).

### Files touched

- New: `app/Http/Controllers/DashboardController.php`
- New: `app/Services/DashboardService.php`
- New: `app/Repositories/Contracts/DashboardRepositoryInterface.php`
- New: `app/Repositories/DashboardRepository.php`
- New: `database/factories/DepartmentFactory.php` (needed for tests)
- New: `tests/Feature/Dashboard/DashboardTest.php`
- New: `specs/feature-1-dashboard.md`
- Modified: `app/Providers/AppServiceProvider.php` — bound `DashboardRepositoryInterface`
- Modified: `routes/web.php` — `/dashboard` now `[DashboardController::class, 'index']`
- Modified: `resources/js/Pages/Dashboard.tsx` — reads `widgets` prop, renders role-aware sections, polls headcount every 60s

---

## Feature 2 — Employees (backend, partial) 🟨

**Shipped:** 2026-04-30 (backend create flow + soft-delete + list/show)
**Spec:** [specs/feature-2-employees.md](specs/feature-2-employees.md)
**Tests added:** 9 (in `tests/Feature/Employees/EmployeeCreateTest.php`); pre-existing `PlaceholderRoutesTest::test_employee_routes_open_for_all_roles_with_view_own_permission` rewritten for the tighter Feature-2 permissions.
**Total tests after:** 144 passing, 669 assertions

### What's in this slice

Backend N-tier for the `Employees` resource is wired:

- `StoreEmployeeRequest` — full validation: Egyptian National ID 14-digit format starting with 2 or 3, Egyptian mobile 11-digit format starting with 010/011/012/015, expat conditional fields (passport + work permit + expiry), org-scoped `exists` rules on department/position/office/manager.
- `EmployeeRepository implements EmployeeRepositoryInterface` — list/find/create/update/softDelete + `generateEmployeeCode(orgId)` produces sequential `YZH-{org}-{0001}` codes.
- `EmployeeService::create` — wraps in `transaction()`: creates `User` row first (with random password — first-login flow lands later), assigns `employee` role, creates `Employee` linked to user, closes loop with `user.employee_id`. Universal law-minimum leave balances seeded inline per Decision 18 (15 annual / 7 casual / 0 sick — sick accrues per Egyptian Labor Law art. 54 once Feature 6 ships).
- `EmployeeController` — `index` (paginated list with manager-scope filter for managers), `show` (self / team / any per permission), `store` (HR/Admin only), `destroy` (soft-delete with required reason).
- 4 routes wired at `/employees` with permission middleware on each verb. Old placeholder route deleted.
- `PositionFactory` + `OfficeFactory` added (were missing; needed for the test setup helper).

### What's NOT yet in this slice (next session)

- Tier-aware update flow (`UpdateEmployeeRequest`, Tier 1/2/3 authorization, change-request queue for Tier 2)
- `EmployeeController::update` + `restore` + `terminate` actions
- Photo upload + storage + preview
- Frontend: list page (`/employees`), detail page (`/employees/{id}`), create form, edit form, delete confirmation modal
- Remaining ~20 Pest tests across `EmployeeListTest`, `EmployeeUpdateTest`, `EmployeeDeleteRestoreTest`

### Routes live now

| Verb | URL | Auth requirement |
|---|---|---|
| `GET` | `/employees` | `employees.view.team` or `employees.view.any` |
| `POST` | `/employees` | `employees.create` |
| `GET` | `/employees/{id}` | `employees.view.own` (self) / `view.team` (own team) / `view.any` (HR+) |
| `DELETE` | `/employees/{id}` | `employees.delete` (HR/Admin) |

### Compliance citations
- Decision 18 — universal law-minimum leave balances initialized on create (annual = 15, casual = 7, sick = 0 baseline)
- Decision 8 — money in piasters (integer column `base_salary_piasters`)
- Egyptian Labor Law: National ID validation per civil registry format (14 digits, 2 = 1900s birth, 3 = 2000s)

### Files touched

- New: `app/Http/Controllers/EmployeeController.php`
- New: `app/Http/Requests/StoreEmployeeRequest.php`
- New: `app/Services/EmployeeService.php`
- New: `app/Repositories/Contracts/EmployeeRepositoryInterface.php`
- New: `app/Repositories/EmployeeRepository.php`
- New: `database/factories/PositionFactory.php`
- New: `database/factories/OfficeFactory.php`
- New: `tests/Feature/Employees/EmployeeCreateTest.php`
- Modified: `app/Providers/AppServiceProvider.php` — bound `EmployeeRepositoryInterface`
- Modified: `routes/web.php` — replaced `/employees` placeholder with the resource routes
- Modified: `tests/Feature/Layout/PlaceholderRoutesTest.php` — rewrote employee-route test for the tighter permission gate

---

## Feature 3 — Org Chart ✅

**Shipped:** 2026-05-04
**Spec:** [specs/feature-3-org-chart.md](specs/feature-3-org-chart.md)
**Tests added:** 8 (in `tests/Feature/OrgChart/OrgChartTest.php`)
**Total tests after:** 267 passing, 1298 assertions

### What it does

Read-only reporting tree at `GET /org-chart`. Any authenticated user can view; clicking a node navigates to `/employees/{id}` (gated by the existing `employees.view.*` policies). The chart is a forest: every active employee with `manager_id IS NULL` becomes a root; their direct reports are children, recursively.

The service applies two important data-cleaning rules:
- **Inactive skip-up.** Terminated/inactive employees are excluded as nodes. Their reports re-parent to the next active ancestor up the chain rather than orphaning.
- **Cycle-safe.** A visited-set guards both the upward walk (`findActiveAncestor`) and the downward render (`serializeNode`). Bad data (manager_id loop) becomes a `Log::warning` + duplicate roots, not an infinite recursion.

### Where to find it

- Route: `GET /org-chart` → `org-chart.index`
- Controller: [`app/Http/Controllers/OrgChartController.php`](app/Http/Controllers/OrgChartController.php)
- Service: [`app/Services/OrgChartService.php`](app/Services/OrgChartService.php) (no repository — single SELECT through the model with eager loads)
- Frontend: [`resources/js/Pages/OrgChart.tsx`](resources/js/Pages/OrgChart.tsx) using `react-organizational-chart ^2.x`

### How to verify

1. Sign in as any seeded test user (`admin@yzh.test` / `password` works).
2. Click "Org chart" in the People sidebar group.
3. You should see the seeded reporting tree: 4 manager roots, ~22 ICs distributed under them, each clickable to navigate to that employee's detail page.
4. Inactive employees (none seeded by default) would not appear as nodes; their reports would re-parent up.

### Key tests

- `it redirects guests to login`
- `it renders for any authenticated user`
- `it returns NULL-manager employees as roots`
- `it nests reports recursively` ← 4-level deep tree resolves correctly
- `it returns empty roots when there are no employees`
- `it excludes terminated employees and re-parents their reports to the manager above` ← skip-up
- `it does not leak employees from another org` ← multi-tenant
- `it does not infinite-loop when two managers point at each other` ← cycle safety

### Edge cases handled

- Cycles in `manager_id` (data error) — visited-set short-circuits, logs warning, treats both as separate roots.
- Inactive ancestor — reports re-parent up the chain, not orphaned.
- Empty org — empty state with copy.
- Multi-tenant — `OrgScope` + explicit test verifying org-A cannot see org-B's tree.

### Compliance citations

None. Read-only aggregation.

### Known limitations / deferred

- **Department-hierarchy mode** (rollup of `parent_department_id`) — deferred. Reporting tree is the primary mental model; revisit when users actually ask.
- **Print stylesheet** — deferred. CSS-only addition when a user asks for a printed org chart.
- **Pan/zoom controls** — deferred. The default horizontal-scroll wrap on narrow viewports is sufficient for v1.

### Files touched

- New: `app/Http/Controllers/OrgChartController.php`
- New: `app/Services/OrgChartService.php`
- New: `resources/js/Pages/OrgChart.tsx`
- New: `tests/Feature/OrgChart/OrgChartTest.php`
- New: `specs/feature-3-org-chart.md`
- Modified: `routes/web.php` — replaced `/org-chart` placeholder with the controller route
- Modified: `package.json` — added `react-organizational-chart`

---

## Feature 4 — My Schedule ✅

**Shipped:** 2026-05-05
**Spec:** [specs/feature-4-my-schedule.md](specs/feature-4-my-schedule.md)
**Tests added:** 12 (in `tests/Feature/Schedule/ScheduleTest.php`)
**Total tests after:** 279 passing, 1418 assertions

### What it does

Weekly schedule view at `GET /schedule` (permission-gated `attendance.view.own`). Shows the authenticated employee's workweek at a glance:

- **Shift strip** — shift start + end times from `employees.shift_start_time` / `shift_end_time`, formatted 12-hour AM/PM. Shows "–:–:–" stub for the live hours counter (wired in Feature 5).
- **Sign In stub** — visually-disabled CTA with copy "Coming with Attendance (Feature 5)". No real check-in logic.
- **Weekly 7-column grid** — Sun through Sat. Workweek days visually distinct (from `employees.workweek_days`, default Sun-Thu per Egyptian convention). Today's column has thicker ring border. Holidays from the `holidays` table shown in a gold strip inside their column.
- **Week navigation** — prev/next week buttons via `router.get('/schedule', { week }, { replace: true, preserveState: true })`. URL param `?week=YYYY-MM-DD` selects the week; invalid params fall back silently to current week.
- **Empty state** — if the user has no employee record, shows "No schedule configured. Contact HR to assign a shift."

### Where to find it

- Route: `GET /schedule` → `schedule.index`
- Controller: [`app/Http/Controllers/ScheduleController.php`](app/Http/Controllers/ScheduleController.php)
- Service: [`app/Services/ScheduleService.php`](app/Services/ScheduleService.php)
- Repository: [`app/Repositories/ScheduleRepository.php`](app/Repositories/ScheduleRepository.php) implements [`ScheduleRepositoryInterface`](app/Repositories/Contracts/ScheduleRepositoryInterface.php), bound in [`AppServiceProvider`](app/Providers/AppServiceProvider.php)
- Frontend: [`resources/js/Pages/Schedule.tsx`](resources/js/Pages/Schedule.tsx)
- Factory: [`database/factories/HolidayFactory.php`](database/factories/HolidayFactory.php) (new — needed for holiday tests)

### How to verify (per role)

1. `php artisan migrate:fresh --seed` then sign in as any seeded test user.
2. Navigate to `/schedule`. You should see today's shift hours and the current week's grid.
3. Click the prev/next week arrows — the URL updates to `?week=YYYY-MM-DD` and the grid re-renders without a full page reload.
4. The "Sign In" button is gray/disabled with stub copy.

### Key tests

- `it redirects guests to login`
- `it renders for any authenticated employee`
- `it blocks users without attendance.view.own permission`
- `it returns 7 days for the current week`
- `it marks workweek days correctly` — Sun-Thu workdays, Fri-Sat off
- `it exposes shift hours from the employee record`
- `it returns null employee when user has no employee record`
- `it navigates to a specific week via ?week param`
- `it falls back to current week on invalid ?week param`
- `it shows org holidays on their date`
- `it does not show holidays from another org` ← multi-tenant
- `it only shows the authenticated users own schedule` ← isolation

### Edge cases handled

- User with no employee record (`user.employee_id IS NULL`) — `employee` prop null, empty state shown, no crash
- Invalid `?week` param — silently falls back to current week's Sunday
- Holiday on a workday AND a day-off — both markers coexist in the day cell
- Multi-tenant isolation — OrgScope filters holidays to the user's org; explicit test verifies cross-org isolation

### Compliance citations

None. Read-only display of shift configuration from the employee record.

### Known limitations / deferred

- **Real check-in/out** — Feature 5 (Attendance). The "Sign In" button is a stub.
- **Live HH:MM:SS counter** — Feature 5. Currently renders "–:–:–".
- **Team schedule view** (manager seeing team's schedule) — deferred; Feature 9 (Settings) will wire shift assignment.

### Files touched

- New: `app/Http/Controllers/ScheduleController.php`
- New: `app/Services/ScheduleService.php`
- New: `app/Repositories/Contracts/ScheduleRepositoryInterface.php`
- New: `app/Repositories/ScheduleRepository.php`
- New: `resources/js/Pages/Schedule.tsx`
- New: `tests/Feature/Schedule/ScheduleTest.php`
- New: `specs/feature-4-my-schedule.md`
- New: `database/factories/HolidayFactory.php`
- Modified: `app/Providers/AppServiceProvider.php` — bound `ScheduleRepositoryInterface`
- Modified: `routes/web.php` — replaced `/schedules` placeholder with the real schedule route

---

## Feature 6 — My Leave ✅

**Shipped:** 2026-05-05
**Spec:** [specs/feature-6-my-leave.md](specs/feature-6-my-leave.md)
**Tests added:** 21 (in `tests/Feature/Leave/MyLeaveTest.php`)
**Total tests after:** 300 passing, 1498 assertions

### What it does

Full leave-request lifecycle at `GET /my-leave` (permission: `leave.view.own`):

- **Three-tab view** — Pending / Approved / Rejected via `?tab=` param. Empty state shown when no requests in that status.
- **New Leave Request form** — "New request" button toggles `NewLeaveForm` inline. Uses Inertia `useForm` (never raw `<form>`). Leave type dropdown shows balance and advance-notice warning. Medical certificate upload appears when the type requires it.
- **Egyptian law compliance:**
  - Per Art. 89: annual leave 21 days, manager-discretionary
  - Per Art. 54: sick leave is a right; 3+ calendar-day spans require a medical certificate (PDF/JPEG/PNG/WebP ≤ 10 MB)
  - Per Art. 90: casual leave 7 days/year
  - Per Art. 93 (Law 29/2025): maternity 120 days, female-only gate
  - Per Art. 93 bis: paternity 1 day per birth
  - Per Art. 94: study leave requires 10-day advance notice
- **Decision 13** — employee with `manager_id IS NULL` auto-approves on submit, balance decremented immediately, no approval queue needed
- **Day-count helper** (`LeaveService::countWorkdays`) excludes Fri + Sat (Egyptian weekend default) and org public holidays
- **Cancel flow** — pending requests cancel directly (soft-delete); approved requests require HR override via `POST /my-leave/{id}/cancel-with-override` (permission: `leave.edit.any`)
- **6 leave types seeded** via `LeaveTypeSeeder` with Egyptian law citations

### Where to find it

- Route: `GET /my-leave` → `leave.index`; `POST /my-leave` → `leave.store`
- Controller: [`app/Http/Controllers/LeaveController.php`](app/Http/Controllers/LeaveController.php)
- FormRequest: [`app/Http/Requests/StoreLeaveRequest.php`](app/Http/Requests/StoreLeaveRequest.php)
- Service: [`app/Services/LeaveService.php`](app/Services/LeaveService.php)
- Repositories: [`LeaveTypeRepository`](app/Repositories/LeaveTypeRepository.php) + [`LeaveRequestRepository`](app/Repositories/LeaveRequestRepository.php)
- Frontend: [`resources/js/Pages/Leave/Index.tsx`](resources/js/Pages/Leave/Index.tsx) + [`NewLeaveForm.tsx`](resources/js/Pages/Leave/NewLeaveForm.tsx)
- Seeder: [`database/seeders/LeaveTypeSeeder.php`](database/seeders/LeaveTypeSeeder.php)

### Key tests

- `it redirects guests to login`
- `it renders the page for an authenticated employee`
- `it returns pending requests tab by default`
- `it filters by approved tab`
- `it creates a pending leave request` (with manager)
- `it auto-approves when employee has no manager (Decision 13)`
- `it decrements the leave balance on auto-approval`
- `it rejects start_date in the past`
- `it rejects end_date before start_date`
- `it rejects a request that exceeds the balance`
- `it rejects overlapping requests`
- `it rejects sick leave 3+ days without medical certificate`
- `it rejects study leave with less than 10 days advance notice`
- `it rejects maternity leave for male employees`
- `it accepts sick leave with valid certificate attachment`
- `it rejects attachments over 10 MB`
- `it cancels a pending request`
- `it blocks cancelling another employees request`
- `it excludes weekends from day count`
- `it excludes public holidays from day count`
- `it only returns the authenticated employees own leave requests` ← multi-tenant

### Compliance citations

```php
// Per Labor Law 14/2025 Art. 89 — annual leave entitlement scales by tenure
// Per Labor Law 14/2025 Art. 54 — sick leave is a right, not discretionary
// Per Labor Law 14/2025 Art. 90 — casual leave 7 days/year
// Per Labor Law 14/2025 Art. 93 (Law 29/2025) — maternity 120 days, female only
// Per Labor Law 14/2025 Art. 93 bis — paternity 1 day per birth, max 3 instances
// Per Labor Law 14/2025 Art. 94 — study leave requires 10-day advance notice
// Per Decision 13: NULL manager_id auto-approves own requests, HR notified via audit log
```

### Known limitations / deferred

- **Weekend confirmation modal** (ANA-2.17) — deferred; form currently allows Fri/Sat dates. The day-count correctly excludes them; a warning modal can be added as a polish item.
- **Leave interaction matrix** (ANA-3.10) — deferred to Feature 17 Compliance Workflows.
- **Sick leave layered payment** (75%/85%/0%) — deferred to Feature 15 Payroll.
- **HR balance adjustment tool** — Feature 9 (Settings).

### Files touched

- New: `app/Http/Controllers/LeaveController.php`
- New: `app/Http/Requests/StoreLeaveRequest.php`
- New: `app/Services/LeaveService.php`
- New: `app/Repositories/Contracts/LeaveTypeRepositoryInterface.php`
- New: `app/Repositories/Contracts/LeaveRequestRepositoryInterface.php`
- New: `app/Repositories/LeaveTypeRepository.php`
- New: `app/Repositories/LeaveRequestRepository.php`
- New: `app/Models/LeaveType.php`
- New: `app/Models/LeaveRequest.php`
- New: `resources/js/Pages/Leave/Index.tsx`
- New: `resources/js/Pages/Leave/NewLeaveForm.tsx`
- New: `database/migrations/2026_05_05_060000_create_leave_types_table.php`
- New: `database/migrations/2026_05_05_060001_create_leave_requests_table.php`
- New: `database/factories/LeaveTypeFactory.php`
- New: `database/factories/LeaveRequestFactory.php`
- New: `database/seeders/LeaveTypeSeeder.php`
- New: `tests/Feature/Leave/MyLeaveTest.php`
- New: `specs/feature-6-my-leave.md`
- Modified: `app/Providers/AppServiceProvider.php` — bound LeaveType + LeaveRequest repositories
- Modified: `routes/web.php` — replaced `/leave` placeholder with 4 leave routes
- Modified: `database/seeders/DatabaseSeeder.php` — added LeaveTypeSeeder

---

## Feature 7: Approvals (Manager/HR view)
**Shipped:** 2026-05-05
**Tests:** +17 new (317 total)
**Files:**
- `specs/feature-7-approvals.md`
- `tests/Feature/Approvals/ApprovalsTest.php`
- `app/Http/Controllers/ApprovalController.php` (60 lines)
- `app/Http/Requests/ApproveLeaveRequest.php`
- `app/Http/Requests/RejectLeaveRequest.php`
- `app/Services/ApprovalService.php` (108 lines)
- `app/Repositories/Contracts/LeaveRequestRepositoryInterface.php` (added paginateForManager/paginateForHr)
- `app/Repositories/LeaveRequestRepository.php` (added paginateForManager/paginateForHr)
- `resources/js/Pages/Approvals/Index.tsx` (228 lines)
- `routes/web.php`
- `TASK_MANAGER.md`

**Key decisions implemented:**
- Decision 19: manager blocked from rejecting `is_right_not_discretion = true` leave types; only HR can
- ANA-3.18: terminated manager (soft-deleted employee) → empty manager queue, requests cascade to HR automatically
- Balance decremented on approve (same `LeaveService::adjustBalance`)
- All writes through `$this->transaction()` → Auditable trait logs before/after diff

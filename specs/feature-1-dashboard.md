# Feature 1 — Dashboard

## Status
🟨 In progress

## PRD reference
- PRD §8 Feature 1
- TASK_MANAGER Feature 1 done-when checklist
- Decision 27 (Headcount widget)

## User stories per role

- **Employee** — Lands on `/dashboard`, sees their leave balance summary, "today's schedule" stub (real data when Attendance F5 ships), pending requests count stub (real when Leave F6 ships), and "coming soon" placeholders for payslip/tasks.
- **Manager** — Same as employee + team status stub (F5), pending approvals count stub (F7).
- **HR** — Headcount widget (Decision 27 — real total headcount, simplified breakdown with what data exists today, full breakdown when Attendance + Leave land), pending requests summary stub, department breakdown (real from `employees` × `departments`).
- **Admin** — All of HR + system metrics (real user count, last 5 successful logins from `audit_logs`, audit log preview).

## Routes
- `GET /dashboard` (existing) — now backed by `DashboardController::index()` instead of the inline closure.

## Data sources

| Widget | Source today | Future swap |
|---|---|---|
| Hero greeting (Hello {firstName}) | `auth.user` (Inertia shared) | — |
| Today's date | `formatDate(new Date())` client-side | — |
| Leave balance card (employee) | `employees.{annual_leave_balance, sick_leave_balance, casual_leave_balance, emergency_leave_balance}` | — |
| Headcount hero (HR/Admin/Manager) | `Employee::active()->count()` (where `employment_status = 'active'`) | — |
| Department breakdown (HR/Admin) | `employees` joined `departments`, GROUP BY department | — |
| Recent logins (Admin) | `audit_logs` WHERE `event = 'login.success'` ORDER BY id DESC LIMIT 5 | — |
| System metrics (Admin) | `User::count()`, `audit_logs::count()`, `Holiday::count()` | — |
| Today's schedule | placeholder | F5 Attendance |
| Pending approvals count (Manager) | placeholder | F7 Approvals |
| Pending requests count (Employee) | placeholder | F6 Leave |
| Coming soon: payslip | placeholder | F15 Payroll (deferred) |

## Edge cases
- User has no employee record (HR creates account but employee row is null): degrade gracefully, hide leave balance card, show identity widget only.
- User has no role assigned: dashboard renders the employee variant (least privilege default).
- Empty department list (fresh tenant): department breakdown shows empty state, not blank.
- Admin viewing as their own org only — `OrgScope` handles isolation; verify in test.
- 60-second polling for headcount widget — only HR/Admin/Manager; not Employee. Polling pauses when tab is hidden.

## Acceptance criteria
- [ ] `DashboardController::index()` exists and is wired at `GET /dashboard`
- [ ] `DashboardService::getDataForRole(User $user): array` composes payload based on role
- [ ] `DashboardRepositoryInterface` declared in `app/Repositories/Contracts/`, bound in `AppServiceProvider`
- [ ] `DashboardRepository` extends `BaseRepository`, no business logic
- [ ] Inertia page receives a `widgets` prop with role-appropriate keys only (no leakage of admin data to non-admins)
- [ ] Each card is a separate React component under `Pages/Dashboard/Widgets/` (or similar)
- [ ] Empty state for each card when its data is missing
- [ ] Loading state via `<Skeleton>` (existing primitive)
- [ ] Mobile responsive (stacks cards vertically below `sm:`)
- [ ] Engineering Studio language: section identifiers (`00`, `01`, `02`...), monospace metadata, stamped CTAs where applicable, gold annotation only
- [ ] Headcount polls every 60 seconds via `setInterval` (only when role has access; pauses on `document.visibilitychange` hidden)
- [ ] All 4 roles tested: each sees their own dashboard, can't see others' restricted widgets

## Compliance citations
- None for Feature 1 (read-only aggregation, no Egyptian Labor Law calculations).

## Test plan

`tests/Feature/Dashboard/DashboardTest.php`:

- `it loads the dashboard for an employee with employee-tier widgets only`
- `it loads the dashboard for a manager with team widgets`
- `it loads the dashboard for an HR user with headcount + department breakdown`
- `it loads the dashboard for an admin with system metrics + recent logins`
- `it does not leak admin widgets to a non-admin`
- `it returns the correct headcount for the user's org only` (multi-tenancy)
- `it returns 0 headcount for a fresh org with no employees` (empty edge case)
- `it returns leave balance from the employee record`
- `it returns null leave balance widget when user has no employee record`
- `it returns recent logins from audit_logs ordered desc limited 5`

`tests/Feature/Dashboard/HeadcountPollingTest.php`:

- `it returns just the headcount payload from a partial reload key for polling` (Inertia partial reloads)

## Implementation notes
- Widget keys in the `widgets` prop are conditional — HR's payload doesn't include `system_metrics`, only Admin's does. Tests verify the absence.
- Polling uses Inertia's [partial reload](https://inertiajs.com/partial-reloads) with `only: ['widgets.headcount']` — minimal payload, no full re-render.
- Service composes data into a flat `widgets` dictionary for cleaner frontend access (`page.props.widgets.headcount.total` etc.) instead of nested by role.

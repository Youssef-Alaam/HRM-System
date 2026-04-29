# YZH-HR — Product Requirements Document (v2)

> **Stack:** Laravel 11 + Inertia.js + React + TypeScript + MySQL 8 + InMotion VPS
> **Architecture:** N-tier (Controller → FormRequest → Service → Repository → Model)
> **Approach:** Foundation built up-front, then features built **one at a time, supervised by Walid**
> **Companion docs:** TASK_MANAGER.md (build order), DECISIONS.md (why), EGYPT_COMPLIANCE_RULES.md (legal), CLAUDE.md (root code conventions), LOCAL_SETUP.md (Day 0 setup)
> **Last updated:** 2026-04-28

---

## How Claude Code uses this PRD

1. **First session:** Read this fully. Confirm understanding.
2. **Every session:** Refer to this when implementing any feature.
3. **Don't invent scope.** If the PRD doesn't say it, ask Walid.
4. **Feature Phase rule:** Build ONE feature, get Walid's approval, then move on. Do NOT build multiple features ahead.
5. **Cross-reference:**
   - **What** to build → this doc
   - **Why** decisions were made → DECISIONS.md
   - **Legal rules** → EGYPT_COMPLIANCE_RULES.md
   - **Build order** → TASK_MANAGER.md
   - **Code conventions** → CLAUDE.md (root)
   - **Setup steps** → LOCAL_SETUP.md

---

## 1. Mission

Replace Mawared HR at YZH Solutions with an internal-built system that:
- Matches all critical Mawared functionality
- Adds improvements (verdict-based face verification, AI assistant later, better UX)
- Complies fully with Egyptian labor law
- Gives YZH full ownership of HR data and workflows
- Architects toward future SaaS commercialization (multi-tenant ready from Day 1)

**Not goals:**
- Multi-currency calculations (EGP only — Decision 11)
- Peer-to-peer shift swaps (use Change Shift via manager — Decision 12)
- White-labeling, billing, subdomain routing (Phase 3 SaaS)
- Native mobile app in Phase 1 (Phase 2 = Flutter)
- PWA install/offline (deferred to Flutter)

---

## 2. User roles

Per Decision 24 (multi-tenant ready) and Cycle 1+ refinement:

| Role | Scope | Examples |
|---|---|---|
| **Admin** | Full system access | YZH IT admin, system configuration |
| **HR** | All employee data, all requests, no system settings | Sarah from HR |
| **Manager** | Their direct reports only | Department/team leads |
| **Employee** | Their own data only | Most users |

Special cases:
- **CEO / NULL manager_id:** auto-approves own requests (Decision 13)
- **Super-admin (Phase 3):** can bypass org_id scope (uses `withoutGlobalScope`)

---

## 3. Permission matrix

> **Updated 2026-04-29 (Cycle 1+ refinement):** moved from a coarse action-per-row table to a granular `{domain}.{action}.{scope}` catalog. Drivers: (a) the upcoming Settings → Staff toggle UI needs many switches, not a few buckets; (b) renaming permissions after they're referenced in middleware/policies/tests is expensive, so be specific now; (c) per-user overrides (grant/revoke on top of role defaults) are now first-class.

### 3.1 Roles and the "default bundle" model

Each of the four roles (`admin`, `hr`, `manager`, `employee`) is a **default bundle of permissions**. Users inherit their role's permissions but can have additional permissions **directly granted** or **directly revoked** at the user level. Direct grants/revokes always win over role defaults.

This is what enables UI like "this manager has the manager role, but I'm switching off `chat.send` for them specifically" without inventing a new role.

### 3.2 Granular permission catalog

Permissions are named `{domain}.{action}` or `{domain}.{action}.{scope}`. Scope qualifiers: `own` (just me), `team` (people who report to me, transitively), `any` (everyone in org).

| Permission | Employee | Manager | HR | Admin |
|---|:-:|:-:|:-:|:-:|
| **employees** | | | | |
| `employees.view.own` | ✓ | ✓ | ✓ | ✓ |
| `employees.view.team` | ✗ | ✓ | ✓ | ✓ |
| `employees.view.any` | ✗ | ✗ | ✓ | ✓ |
| `employees.edit.own.tier1` (phone, address, emergency contact) | ✓ | ✓ | ✓ | ✓ |
| `employees.edit.own.tier2` (marital, dependents, bank — HR-approved request) | ✗ | ✗ | ✓ | ✓ |
| `employees.create` | ✗ | ✗ | ✓ | ✓ |
| `employees.edit.any` | ✗ | ✗ | ✓ | ✓ |
| `employees.delete` (soft) | ✗ | ✗ | ✓ | ✓ |
| `employees.restore` | ✗ | ✗ | ✗ | ✓ |
| `employees.terminate` | ✗ | ✗ | ✓ | ✓ |
| `employees.confirm_deemed_resignation` | ✗ | ✗ | ✓ | ✓ |
| `employees.override_foreign_quota` | ✗ | ✗ | ✓ | ✓ |
| **attendance** | | | | |
| `attendance.checkin.own` | ✓ | ✓ | ✓ | ✓ |
| `attendance.view.own` | ✓ | ✓ | ✓ | ✓ |
| `attendance.view.team` | ✗ | ✓ | ✓ | ✓ |
| `attendance.view.any` | ✗ | ✗ | ✓ | ✓ |
| `attendance.edit.any` | ✗ | ✗ | ✓ | ✓ |
| `attendance.correct.own` (within 24h) | ✓ | ✓ | ✓ | ✓ |
| `attendance.correct.team` | ✗ | ✓ | ✓ | ✓ |
| `attendance.assign_shifts.team` | ✗ | ✓ | ✓ | ✓ |
| `attendance.assign_shifts.any` | ✗ | ✗ | ✓ | ✓ |
| **leave** | | | | |
| `leave.request.own` | ✓ | ✓ | ✓ | ✓ |
| `leave.view.own` | ✓ | ✓ | ✓ | ✓ |
| `leave.view.team` | ✗ | ✓ | ✓ | ✓ |
| `leave.view.any` | ✗ | ✗ | ✓ | ✓ |
| `leave.approve.team` (manager step) | ✗ | ✓ | ✓ | ✓ |
| `leave.approve.final` (HR step) | ✗ | ✗ | ✓ | ✓ |
| `leave.edit.any` (adjust balances) | ✗ | ✗ | ✓ | ✓ |
| **requests** (non-leave: cert letters, doc requests, etc.) | | | | |
| `requests.create.own` | ✓ | ✓ | ✓ | ✓ |
| `requests.view.own` | ✓ | ✓ | ✓ | ✓ |
| `requests.view.team` | ✗ | ✓ | ✓ | ✓ |
| `requests.view.any` | ✗ | ✗ | ✓ | ✓ |
| `requests.approve.team` | ✗ | ✓ | ✓ | ✓ |
| `requests.approve.final` | ✗ | ✗ | ✓ | ✓ |
| **payroll** | | | | |
| `payroll.run` | ✗ | ✗ | ✓ | ✓ |
| `payroll.lock` | ✗ | ✗ | ✓ | ✓ |
| `payroll.mark_paid` | ✗ | ✗ | ✓ | ✓ |
| `payroll.payslip.edit` (draft only) | ✗ | ✗ | ✓ | ✓ |
| `payroll.payslip.view.own` | ✓ | ✓ | ✓ | ✓ |
| `payroll.payslip.view.team` | ✗ | ✗ | ✓ | ✓ |
| `payroll.payslip.view.any` | ✗ | ✗ | ✓ | ✓ |
| **org structure** | | | | |
| `org.departments.manage` | ✗ | ✗ | ✓ | ✓ |
| `org.positions.manage` | ✗ | ✗ | ✓ | ✓ |
| `org.offices.manage` (add/edit, set GPS radius) | ✗ | ✗ | ✓ | ✓ |
| `org.holidays.manage` | ✗ | ✗ | ✓ | ✓ |
| `org.holidays.bulk_apply` | ✗ | ✗ | ✓ | ✓ |
| **users & access** | | | | |
| `users.create` | ✗ | ✗ | ✗ | ✓ |
| `users.disable` | ✗ | ✗ | ✗ | ✓ |
| `users.assign_roles` | ✗ | ✗ | ✗ | ✓ |
| `users.assign_permissions` (direct grant/revoke per user) | ✗ | ✗ | ✗ | ✓ |
| `users.unlock` (clear `locked_at`) | ✗ | ✗ | ✓ | ✓ |
| **chat / messaging** | | | | |
| `chat.send` | ✓ | ✓ | ✓ | ✓ |
| `chat.view.team` | ✗ | ✓ | ✓ | ✓ |
| `chat.view.any` | ✗ | ✗ | ✓ | ✓ |
| **availability** | | | | |
| `availability.view.own` | ✓ | ✓ | ✓ | ✓ |
| `availability.view.team` | ✗ | ✓ | ✓ | ✓ |
| `availability.view.any` | ✗ | ✗ | ✓ | ✓ |
| `availability.set.own` | ✓ | ✓ | ✓ | ✓ |
| `availability.set.team` | ✗ | ✓ | ✓ | ✓ |
| **announcements** | | | | |
| `announcements.view` | ✓ | ✓ | ✓ | ✓ |
| `announcements.create` | ✗ | ✗ | ✓ | ✓ |
| **audit & settings** | | | | |
| `audit.view` | ✗ | ✗ | ✗ | ✓ |
| `settings.edit` | ✗ | ✗ | ✗ | ✓ |
| **reports & exports** | | | | |
| `reports.run.own` | ✓ | ✓ | ✓ | ✓ |
| `reports.run.team` | ✗ | ✓ | ✓ | ✓ |
| `reports.run.any` | ✗ | ✗ | ✓ | ✓ |
| `exports.own` | ✓ | ✓ | ✓ | ✓ |
| `exports.team` | ✗ | ✓ | ✓ | ✓ |
| `exports.any` | ✗ | ✗ | ✓ | ✓ |
| `exports.pdpl_self_service` (own data export per PDPL) | ✓ | ✓ | ✓ | ✓ |
| **security** | | | | |
| `security.step_up.required` (flag forces step-up on sensitive ops) | ✗ | ✗ | ✓ | ✓ |

### 3.3 Per-user overrides

Beyond the default bundles above, an admin can:
- **Grant** an additional permission to a user (`UserPermissionService::grant($user, 'chat.view.any', $reason)`)
- **Revoke** a permission a user would otherwise inherit from their role (`revoke($user, 'chat.send', $reason)`)
- **Reset** a user back to their role's defaults (clear all direct grants and revokes)

All grant/revoke/reset actions are written to `audit_logs` with the actor, target, permission, reason, and IP. The Settings → Staff Management UI (Feature Phase) renders one toggle per permission; toggles default to the role's value but show a visual indicator when overridden.

### 3.4 Implementation

- **Package:** `spatie/laravel-permission`. Each row in §3.2 = one row in `permissions` table.
- **Roles:** seeded by `RolePermissionSeeder`, idempotent. Default bundles defined in `app/Permissions/RoleDefinitions.php` so they're code-reviewable.
- **Direct grants/revokes:** Spatie's per-user permission storage is used directly. `UserPermissionService` wraps it to enforce the audit-log requirement (no direct calls to `$user->givePermissionTo` outside the service).
- **Middleware on routes:** `permission:employees.edit.any`, `permission:payroll.run`. Multiple permissions: `permission:leave.approve.team|leave.approve.final`.
- **Policies on models:** Permission alone is not enough for `team` and `own` scopes. Example `EmployeePolicy::view(User $actor, Employee $target)` returns true if `$actor->can('employees.view.any')` OR (`$actor->can('employees.view.team')` AND `$target->isInTeamOf($actor)`) OR (`$actor->can('employees.view.own')` AND `$target->id === $actor->employee_id`).
- **Naming convention:** lowercase, dot-separated, `{domain}.{action}[.{scope}]`. Renames after seeding require a migration (rename in DB + grep update across codebase).

---

## 4. Architecture: N-tier (mandatory)

### Layers

```
Presentation (React/Inertia pages)
   ↓
Controller (HTTP entry point)
   ↓
Validation (FormRequest)
   ↓
Service (business logic)
   ↓
Repository (data access)
   ↓
Model (Eloquent + DB)
```

### Folder structure

```
yzh-hr/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Requests/         # FormRequest validation
│   │   ├── Resources/        # response shaping
│   │   └── Middleware/
│   ├── Services/             # business logic
│   ├── Repositories/
│   │   └── Contracts/        # interfaces
│   ├── Models/
│   │   ├── Concerns/         # BelongsToOrg, Auditable traits
│   │   ├── Scopes/           # OrgScope
│   │   └── Observers/        # AuditObserver
│   ├── Policies/
│   ├── Jobs/
│   ├── Console/Commands/
│   ├── Events/
│   └── Listeners/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── resources/
│   ├── js/
│   │   ├── Pages/            # Inertia pages
│   │   ├── Components/
│   │   │   └── ui/           # shadcn components
│   │   ├── Layouts/          # AppLayout.tsx
│   │   ├── lib/
│   │   │   ├── utils.ts
│   │   │   ├── money.ts
│   │   │   ├── dates.ts
│   │   │   └── face-verification.ts
│   │   └── types/
│   └── views/
│       └── app.blade.php     # Inertia root template
├── routes/
│   ├── web.php
│   ├── auth.php
│   └── api.php               # for future Flutter mobile
├── tests/
│   ├── Feature/
│   └── Unit/
├── config/
├── public/
└── storage/
    └── app/
        ├── backups/
        ├── uploads/
        └── selfies/          # 24h auto-delete
```

---

## 5. Tech stack (locked — do not change)

- **Backend:** Laravel 11 + PHP 8.3+
- **Frontend:** Inertia.js + React 18 + TypeScript (strict mode)
- **Build:** Vite
- **Styling:** Tailwind CSS + shadcn/ui (React components)
- **Icons:** lucide-react
- **Database:** MySQL 8
- **ORM:** Eloquent
- **Auth:** Laravel Sanctum
- **RBAC:** Spatie Laravel Permission
- **Storage:** Laravel Storage (filesystem on InMotion VPS)
- **Real-time (when needed):** Laravel Reverb (self-hosted, free)
- **Background jobs:** Laravel Queue + Horizon (Redis driver)
- **Email:** Laravel Mail via Zoho SMTP
- **PDF:** DomPDF or barryvdh/laravel-snappy (for payslips later)
- **Excel:** maatwebsite/excel
- **Forms (frontend):** React Hook Form + Zod (matching backend FormRequest validation)
- **Date handling:** date-fns + date-fns-tz (frontend), Carbon (backend)
- **Charts:** Recharts
- **Backup:** spatie/laravel-backup
- **Tests:** Pest
- **Linting:** ESLint + Prettier (frontend), Laravel Pint (backend)
- **Pre-commit:** Husky + lint-staged
- **Error tracking:** Sentry (Laravel SDK)
- **Face verification:** face-api.js (browser-based, free)
- **Geolocation:** browser Geolocation API (built-in, free)
- **Hosting (Phase 1):** InMotion VPS
- **Domain DNS:** Cloudflare (recommended)
- **CI/CD:** GitHub Actions

---

## 6. Multi-tenancy implementation (Decision 24, 28)

Every business model uses the `BelongsToOrg` trait + `OrgScope` global scope. Auto-sets `org_id` on creating from `auth()->user()->org_id`. Bypass via `withoutGlobalScope(OrgScope::class)` (super-admin only, audit logged).

**Phase 1 reality:** one organization (YZH Solutions); `org_id` effectively constant. **Phase 3:** add tenants without refactoring.

---

## 7. Data model

(Full schema in EGYPT_COMPLIANCE_RULES.md and TASK_MANAGER.md F2.)

### Core tables (Phase 1)
- **organizations** — single row in Phase 1
- **users** — Laravel default + email_verified_at + org_id + employee_id link
- **offices** — physical locations with lat/long for geofencing
- **departments** — hierarchical via parent_department_id
- **positions** — job titles with level
- **employees** — 40+ fields including expat fields, version column, soft-delete, audit
- **holidays** — public holidays per org, recurring or one-time
- **attendance_records** — check-in/out with GPS + verdict + audit
- **leave_requests** — all leave types with approval chain
- **audit_logs** — every write captured

### Conventions
- All money as **integer piasters** (1 EGP = 100 piasters per Decision 8)
- All timestamps stored UTC, displayed Cairo per Decision 10
- Dates without time as `DATE`
- Soft delete via `deleted_at` (Eloquent SoftDeletes)
- Optimistic concurrency via `version` integer column
- Foreign keys enforced
- Indexes on hot paths (employee email, national_id, employee_code, attendance employee_id+date)
- Every business table has `org_id` column

---

## 8. Feature specifications

Each feature mirrors a task in TASK_MANAGER.md. Read TASK_MANAGER.md for full DoD checklists. Spec below adds detail.

### Feature 1: Dashboard
Routes: `/` — role-aware redirect.
Components: `<HeadcountWidget />` (Decision 27), `<TodaysScheduleCard />`, `<LeaveBalanceCard />`, `<PendingRequestsList />`, `<DepartmentBreakdown />`, `<ComingSoonCard />`, `<RecentLogins />`, `<SystemMetrics />`.
N-tier: `DashboardController::index()` → `DashboardService::getDataForRole($user)` → repositories.

### Feature 2: Employees
Routes: full REST under `/employees`.
Validation in `StoreEmployeeRequest`: Egyptian National ID 14-digit regex, Egyptian phone format, unique email, `required_if:is_expat` on passport/work-permit fields.
Service wraps in transaction: User creation, Employee creation, role assignment, initial leave balance via `LeaveService::initializeBalances` (Decision 18).

### Feature 3: Org Chart
Visual hierarchy via `OrgChartService::buildTree($orgId)`.

### Feature 4: My Schedule
Mobile-first. Live hours counter (HH:MM:SS) ticking via React state + interval.

### Feature 5: Attendance (full check-in flow)
- Tap "Sign In" → geolocation → Haversine vs office radius
- Outside radius → block
- Inside → camera permission → selfie capture
- face-api.js compares to reference photo → verdict (verified/possibly_self/unverified)
- POST `/attendance/check-in` with lat, long, accuracy, selfie file, verdict, office_id
- Backend: `CheckInRequest` validates, `AttendanceService::recordCheckIn` creates record, stores selfie, fires event
- Background job (24h later): deletes selfie file
- Verdict `unverified` → notify HR

### Feature 6-8: Leave management
- Annual leave entitlement **calculated, not stored** (per ANA-3.1)
- Sick leave layered payment (75/85/0/100% chronic per ANA-3.2)
- Casual leave 7 days/year max 2 consecutive
- Maternity 120 days × up to 3, blocks termination
- Paternity 1 day × up to 3
- Study leave with 10-day notice + post-facto exam proof
- Weekend confirmation modal on submission (ANA-2.17)
- Public holidays don't count
- Leave interaction matrix (ANA-3.10)
- Approval cascade on terminated approver (ANA-3.18)

---

## 9. Standard states design system

Per SWE-4.4 from Cycle 4, every page handles:
- **Loading:** skeleton or spinner
- **Empty:** friendly icon + headline + subtext + action button
- **Error:** sad icon + plain language + retry/contact button
- **Form errors:** below field, red, specific cause
- **403:** "You don't have permission to view this. Contact HR if you think this is a mistake."
- **404:** "We can't find that page. [Go to Dashboard]"

Reusable components: `<EmptyState />`, `<ErrorState />`, `<FieldError />`, `<NotFoundPage />`, `<ForbiddenPage />`.

---

## 10. Date / time / money formatting (locked)

- **Date format:** `DD MMM YYYY` (e.g., "23 Apr 2026")
- **Time format:** 12-hour with AM/PM (e.g., "3:00 PM")
- **Money format:** `EGP 1,234.56` with thousand separators
- **Storage:** UTC for timestamps, EGP piasters as integers for money, ISO 8601 for date strings

---

## 11. Constraints — what Claude Code MUST do / MUST NOT do

### MUST do
- Use TypeScript strict mode (no `any`)
- Use Eloquent (no raw SQL with string interpolation)
- Use FormRequest for every controller method that accepts input
- Pass through Service + Repository layers (no model calls from controllers)
- Money in piasters (integer)
- Soft delete only (never `DELETE` rows)
- Audit log every write (via Auditable trait)
- File ≤ 200 lines for controllers, ≤ 250 for models, ≤ 300 for React components, ≤ 150 for services
- Use shadcn/ui components, don't reinvent
- Write Pest tests for every service method
- Run `php artisan test` before declaring a task done
- Confirm before destructive operations (delete, terminate)
- Use Laravel Pint for backend formatting, Prettier for frontend
- Reference specific Egyptian law articles in code comments for compliance code

### MUST NOT do
- Use raw SQL with string interpolation
- Use localStorage or sessionStorage in artifacts (Inertia preserves state)
- Use HTML `<form>` tags inside React (use Inertia's `useForm`)
- Hard delete records
- `dd()` or `dump()` in committed code
- Disable global scopes without explicit super-admin check
- Service_role / admin-bypass operations without audit log
- Add features not in this PRD without asking Walid
- Build multiple Feature Phase tasks in parallel (one at a time, supervised)
- Build past Foundation Phase F13 without Walid's approval
- Hard-code Egyptian compliance values (use config/constants pulled from EGYPT_COMPLIANCE_RULES.md, configurable in Settings)

### When stuck
1. Re-read this PRD section relevant to the feature
2. Check DECISIONS.md for related decisions
3. Check EGYPT_COMPLIANCE_RULES.md for legal angle
4. Ask Walid in chat — don't guess on big decisions
5. If still stuck for 2 hours: per WHEN_STUCK.md (`/clear`, then escalating)

---

## 12. Acceptance criteria — Foundation Phase complete

Foundation Phase is complete when:
- [ ] All 12 foundation tasks (F1-F12) marked ✅ in TASK_MANAGER.md
- [ ] `php artisan migrate:fresh --seed` runs cleanly
- [ ] Walid can log in as 4 different roles and see correct sidebar
- [ ] All sidebar items navigate to placeholder pages (no broken routes)
- [ ] Tests pass: auth, RBAC, multi-tenant scope, audit log
- [ ] Backup runs and restores successfully
- [ ] CI runs green on a push
- [ ] F13 (Foundation review checkpoint) approved by Walid

Then Feature Phase begins.

---

## 13. Acceptance criteria — Feature Phase 1 complete

Feature Phase 1 is complete when all 8 sidebar features are ✅ in TASK_MANAGER.md AND each has been individually approved by Walid.

Then Feature Phase 2 begins (Settings, audit log viewer, payroll, etc.).

---

## 14. Reference: locked decisions to honor

(Brief; full text in DECISIONS.md.)

| # | Topic | Rule |
|---|---|---|
| 1-3 | Check-in flow | GPS + selfie + verdict-based face verification (Decision 7 supersedes original 1-3) |
| 4 | Email | Zoho Mail SMTP for notifications |
| 5 | ClickUp | No integration (separate tool) |
| 6 | Expat support | Full support: work permits, passport tracking, expiry alerts, SI claim-back |
| 7 | Face verification | Verdict-based (verified/possibly_self/unverified), reference photo retained, check-in selfies deleted in 24h |
| 8 | Project philosophy | Requirements > timelines |
| 9 | Holidays | Weekday holiday = day off; weekend holiday = lost (no replacement) |
| 10 | Timezone | UTC storage, Cairo display for company, employee-local for personal |
| 11 | Currency | EGP only, never multi-currency |
| 12 | Shift swaps | No peer-to-peer; use "Change Shift" via manager |
| 13 | Top hierarchy | NULL manager_id = auto-approve own requests, HR notified |
| 14 | Plan restructure | Requirements-first, timeline secondary |
| 15-16 | Removals | Peer-to-peer shift swap removed; multi-currency removed |
| 17 | Holiday work | Day off default; HR requests; show up = triple pay; no formal refusal |
| 18 | Leave balances | Universal law-minimum from Day 1; extras added manually by HR |
| 19 | Approval discretion | Annual = manager discretion; sick/permissions/maternity = rights |
| 20 | Hosting (superseded) | Was managed services Phase 0 → now superseded by Decision 22 |
| 21 | Government filings | Generate compliant exports, HR uploads manually |
| 22 | Tech stack | Laravel + Inertia + React + MySQL + InMotion VPS |
| 23 | Mobile | Web-first Phase 1, Flutter native Phase 2 |
| 24 | SaaS readiness | Multi-tenant architected from Day 1, single-tenant Phase 1 |
| 25 | PRD rewrite | This document (v2) replaces v1 |
| 26 | Backup | 10-day rolling daily backups, monthly cold backup |
| 27 | Headcount widget | Visual at-a-glance breakdown on HR/Admin/Manager dashboards |
| 28 | Multi-tenant pattern | OrgScope global scope + BelongsToOrg trait + org_id every business table |

---

## 15. What to ask Walid before Day 1

Before writing any code, ask Walid:
1. "Have you completed LOCAL_SETUP.md (PHP, MySQL, Composer, Node, Redis installed)?"
2. "Should I scaffold the project now, or do you want to do that step manually?"
3. "Do you want Arabic-name seed employees (realistic) or English placeholder names (easier debugging)?"
4. "Any company colors/logo for the YZH branding, or use generic blue?"
5. "Confirm: I should build the entire Foundation Phase (F1-F12) sequentially without per-task review, only checking in at F13. Yes?"

Don't begin coding until these are answered.

---

## Document version

- **v1.0** — Next.js + Supabase (deprecated)
- **v2.0** — Laravel + Inertia + React + MySQL + InMotion VPS (this version)
- **Built from:** Cycles 1-5 review, 28 locked decisions, 53 Mawared screenshots, 142+ flags triaged, sidebar layout from Walid's screenshot

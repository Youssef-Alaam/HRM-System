# YZH-HR — Task Manager

> **The master task list.** Every task to build YZH-HR lives here.
>
> **How to use:**
> - **Start of every session:** Read this file. Find first uncompleted task. That's where you start.
> - **End of every session:** Update task status. Add notes. Mark blockers.
> - **Bottom of file** = where new tasks get added.
> - **Status legend:** ⬜ Not started | 🟨 In progress | 🟦 Awaiting Walid review | ✅ Done | ❌ Blocked
> - Each task has a definition-of-done checklist. **Don't move on until every box is ticked.**
> - **Walid reviews every feature task** before next is started. Foundation tasks proceed in sequence without per-task review (they have no UI to demo).

---

## How tasks are organized

This file has 2 parts:

1. **Foundation Phase** — invisible infrastructure done up-front, sequential, no per-task review
2. **Feature Phase** — visible features in sidebar order, supervised one at a time by Walid

Once Foundation Phase is complete, Feature Phase begins. Within Feature Phase, you do ONE feature at a time, get Walid's approval, then move to the next.

---

## 🏗 Foundation Phase

These are non-negotiable infrastructure tasks. Build them in order. No per-task review needed (no UI yet).

---

### F1: Project scaffold ✅

**Goal:** Laravel + Inertia + React + TypeScript project running locally with `php artisan serve` + `npm run dev`.

**Done when:**
- [x] Laravel 11 installed via Composer
- [x] Inertia.js installed and configured (via Laravel Breeze)
- [x] React + TypeScript installed and configured (Vite)
- [x] Tailwind CSS configured (upgraded to v4)
- [ ] shadcn/ui CLI initialized, base components installed — **DEFERRED**: Tailwind v4 + shadcn integration in this Laravel template needs further config work; install per-component when the first feature needs them via `npx shadcn@latest add <component>`
- [x] `lucide-react` icons available
- [x] App loads at localhost (welcome page + Breeze auth pages all serve 200)
- [x] Hot reload works (Vite dev server)
- [x] `tsconfig.json` strict mode enabled
- [x] Prettier + ESLint configured (`npm run lint` / `npm run lint:fix`)
- [x] `.env.example` checked in, `.env` gitignored
- [x] First commit made

**Caveats logged:**
- **Redis backend** for queue/cache/session — currently set to `database` driver because Windows 11 Hyper-V firewall blocks PHP from reaching Redis running inside WSL Ubuntu. WSL Redis itself is healthy. To re-enable: either install Memurai (native Windows Redis-compat) or add a Hyper-V firewall exception for php.exe.

---

### F2: Database setup + foundational migrations ⬜

**Goal:** MySQL connected, core tables exist, Eloquent in use.

**Done when:**
- [ ] MySQL 8 running locally
- [ ] `.env` configured for MySQL connection
- [ ] `php artisan migrate` runs cleanly
- [ ] Foundational tables created via migrations (in this order):
  - [ ] `organizations` (id, name, legal_name, country, currency='EGP', timezone='Africa/Cairo')
  - [ ] `users` (Laravel default + email_verified_at, modified for our schema)
  - [ ] `offices` (org_id, lat, long, allowed_check_in_radius_meters)
  - [ ] `departments` (org_id, parent_department_id self-ref, head_employee_id nullable)
  - [ ] `positions` (org_id, department_id, level)
  - [ ] `employees` (full schema per PRD §5 — 40+ fields, version column, soft-delete)
  - [ ] `holidays` (org_id, name, date, is_recurring)
  - [ ] `audit_logs` (user_id, action, entity_type, entity_id, changes_json, ip, user_agent)
- [ ] All tables include `org_id`, `created_at`, `updated_at`, `deleted_at` (soft deletes)
- [ ] Foreign keys correct
- [ ] Indexes on hot paths

---

### F3: Auth (Laravel Sanctum) ⬜

**Done when:**
- [ ] Sanctum installed and configured
- [ ] Login endpoint works
- [ ] Logout endpoint works
- [ ] Session persists across page reloads
- [ ] Password reset flow works (Zoho SMTP can be stubbed for now)
- [ ] Failed login: 5 attempts per email per 15 min triggers cooldown
- [ ] Account lock after 10 failed attempts in a day
- [ ] Login event written to audit_logs
- [ ] Logout event written to audit_logs

---

### F4: RBAC via Spatie Permission ⬜

**Done when:**
- [ ] `spatie/laravel-permission` installed
- [ ] Migrations run for permission tables
- [ ] `RolePermissionSeeder` created with full permission matrix per PRD §4
- [ ] All 4 roles created
- [ ] Middleware `role:admin`, `permission:edit_employees` usable on routes
- [ ] Tests verify role access

---

### F5: Audit log infrastructure ⬜

**Done when:**
- [ ] `Auditable` trait created
- [ ] Observer captures changes (before/after diff as JSON)
- [ ] User, IP, user agent captured on every entry
- [ ] Trait applied to `Employee`, `LeaveRequest`, `AttendanceRecord`, `Holiday`, etc.
- [ ] Test verifies audit_logs entry on create

---

### F6: Multi-tenant scope (org_id everywhere) ⬜

**Done when:**
- [ ] `BelongsToOrg` trait created with `OrgScope` global scope
- [ ] Trait auto-sets `org_id` on creating from auth user's org
- [ ] Trait applied to every business model
- [ ] `withoutGlobalScope(OrgScope::class)` works for super-admin operations
- [ ] Test verifies scope isolation

---

### F7: Base layout + sidebar ⬜

**Done when:**
- [ ] `AppLayout.tsx` Inertia layout created
- [ ] Sidebar matches Walid's screenshot (dark theme, YZH HR brand top, sidebar items grouped by PEOPLE/TIME/LEAVE)
- [ ] All nav items render but link to placeholder pages
- [ ] User info card at bottom (avatar + name + role)
- [ ] Sidebar collapse toggle
- [ ] Mobile responsive
- [ ] Active route highlighted
- [ ] Logout button in user dropdown

---

### F8: Seed data ⬜

**Done when:**
- [ ] `OrganizationSeeder` — 1 org (YZH Solutions)
- [ ] `OfficeSeeder` — 1 office (Avenue Mall, Rehab) with real lat/long
- [ ] `DepartmentSeeder` — Technical Department, Sales, HR, Operations
- [ ] `PositionSeeder` — variety of positions per department
- [ ] `HolidaySeeder` — Egyptian 2026 public holidays
- [ ] `EmployeeFactory` — generates realistic Egyptian employee
- [ ] `RolePermissionSeeder` (from F4)
- [ ] Test users: admin@yzh.test, hr@yzh.test, manager@yzh.test, employee@yzh.test (password "password")
- [ ] 25-30 random employees seeded
- [ ] `php artisan migrate:fresh --seed` runs cleanly

---

### F9: Service + Repository layer scaffolding ⬜

**Done when:**
- [ ] Folder structure created (Services, Repositories, Contracts, Requests, Resources)
- [ ] `BaseRepository` abstract with common methods
- [ ] `BaseService` abstract with logging, audit hooks
- [ ] First example feature wired through the layers
- [ ] Documented pattern in CLAUDE.md

---

### F10: Test framework (Pest) ⬜

**Done when:**
- [ ] Pest installed
- [ ] `tests/Feature` and `tests/Unit` directories ready
- [ ] First tests: auth login, RBAC blocks employee from admin route, multi-tenant scope isolates data
- [ ] `php artisan test` runs all tests, all pass

---

### F11: CI/CD baseline ⬜

**Done when:**
- [ ] GitHub repo created and pushed
- [ ] `.github/workflows/test.yml` runs on push and PR
- [ ] Workflow runs: lint, type check, Pest tests
- [ ] Husky + lint-staged pre-commit hook configured
- [ ] First successful CI run

---

### F12: Backup system foundation ⬜

**Done when:**
- [ ] `spatie/laravel-backup` installed and configured
- [ ] Backup destination: local storage `/storage/app/backups/`
- [ ] `php artisan backup:run` creates a successful backup file
- [ ] `php artisan backup:list` shows the backup
- [ ] Cleanup policy: keep daily for 10 days
- [ ] Will be scheduled later when running on prod (Phase 2)

---

### F13: Foundation review checkpoint 🟦

**Walid reviews everything above before Feature Phase starts.**

**Walkthrough checklist:**
- [ ] Walid runs `php artisan migrate:fresh --seed` successfully
- [ ] Walid logs in as admin, sees sidebar layout
- [ ] Walid logs in as employee, sees same sidebar with appropriate permissions
- [ ] Walid clicks each sidebar item and sees placeholder pages
- [ ] Walid runs `php artisan test` and sees all tests pass
- [ ] Walid runs `php artisan backup:run` and verifies backup file exists
- [ ] Walid says "Foundation looks good, start Feature Phase"

---

## 🎨 Feature Phase

Each feature below = a complete vertical slice. **Walid reviews each before moving to next.**

---

### Feature 1: Dashboard 🟦 (will be reviewed)

Status: ⬜ Not started

**Done when:**
- [ ] Route `/` routes to role-appropriate dashboard
- [ ] Employee dashboard: today's schedule card, leave balance card, pending requests, "Coming soon" placeholders for payslip/tasks
- [ ] Manager dashboard: team status today, pending approvals count, "Coming soon" for team performance
- [ ] HR dashboard: **Headcount widget per Decision 27**, pending requests summary, department breakdown
- [ ] Admin dashboard: HR dashboard + system metrics (user count, audit log preview, last 5 logins)
- [ ] Each card is a separate React component
- [ ] Empty states + loading states handled
- [ ] Mobile responsive
- [ ] Headcount widget polls every 60s
- [ ] Test: each role sees their dashboard, can't see others' restricted widgets

**Walid reviews → approves → next feature**

---

### Feature 2: Employees ⬜

**Done when:**
- [ ] Employees list page (`/employees`) — search/filter/sort/pagination
- [ ] Employee detail page (`/employees/[id]`) — tabs for Profile/Attendance/Leave/Documents
- [ ] Create new employee (`/employees/new` — HR/Admin only) with FormRequest + Zod validation
- [ ] National ID 14-digit Egyptian validation
- [ ] Phone Egyptian format validation
- [ ] Email uniqueness
- [ ] Edit employee with tier model (Tier 1 self / Tier 2 HR-approval / Tier 3 admin-only)
- [ ] Soft delete (HR/Admin only) with reason + audit
- [ ] Photo upload + preview
- [ ] Permission tests
- [ ] All N-tier layers used

**Walid reviews → approves → next feature**

---

### Feature 3: Org Chart ⬜

**Done when:**
- [ ] Route `/org-chart`
- [ ] Visual tree using react-organizational-chart
- [ ] Department hierarchy (parent_department_id)
- [ ] Manager → reports tree
- [ ] Click node → expand/collapse
- [ ] Click employee → links to detail page
- [ ] Read-only
- [ ] Print-friendly view
- [ ] Mobile responsive

**Walid reviews → approves → next feature**

---

### Feature 4: My Schedule ⬜

**Done when:**
- [ ] Route `/schedule`
- [ ] Today section: shift hours displayed
- [ ] Large "Sign In" button if not checked in
- [ ] Live HH:MM:SS hours counter when checked in
- [ ] "Sign Out" button when checked in
- [ ] Weekly calendar with scheduled days
- [ ] Date navigation
- [ ] Mobile responsive
- [ ] Empty state for unscheduled days

---

### Feature 5: Attendance (full check-in/out flow) ⬜

**Done when:**
- [ ] Tap "Sign In" → check-in flow opens
- [ ] Browser geolocation permission
- [ ] Haversine distance calculation
- [ ] Outside radius → block with "{distance}m from {office}, must be within {radius}m"
- [ ] Inside radius → camera permission
- [ ] Capture selfie via getUserMedia
- [ ] Compare via face-api.js
- [ ] Verdict: verified (>90%) / possibly_self (70-90%, flag for HR) / unverified (<70%, block + HR notify)
- [ ] Store check-in record (timestamp UTC, lat/long, accuracy, verdict, office_id)
- [ ] Confirmation screen
- [ ] Selfie deleted after 24h via background job
- [ ] Same flow for sign out
- [ ] Attendance history page with edit (HR/Admin only)
- [ ] Late detection via employee workweek_days config

**Walid reviews → approves → next feature**

---

### Feature 6: My Leave ⬜

**Done when:**
- [ ] Route `/my-leave`
- [ ] Tabs: Pending / Approved / Rejected
- [ ] "New Leave Request" form
- [ ] leave_type dropdown
- [ ] Form shows current balance for selected leave type
- [ ] Auto-calculate days excluding public holidays
- [ ] Weekend confirmation modal warning
- [ ] Validation: cannot exceed balance, cannot overlap (matrix from ANA-3.10)
- [ ] Sick leave 3+ days: medical cert upload required
- [ ] Study leave: 10-day advance notice enforced
- [ ] Cancel pending request works
- [ ] Cannot cancel approved without HR override

---

### Feature 7: Approvals (Manager/HR view) ⬜

**Done when:**
- [ ] Route `/approvals`
- [ ] Manager sees pending requests from their team
- [ ] HR sees manager-approved + escalations
- [ ] Approve button (status, balance decrement, notification)
- [ ] Reject button (mandatory reason)
- [ ] Per Decision 19: annual = manager discretion; sick/permissions/casual/maternity/paternity = right
- [ ] CEO/NULL manager_id auto-approves per Decision 13
- [ ] Approver-cascade on terminated approver per ANA-3.18
- [ ] Audit log every action

---

### Feature 8: Leave Calendar ⬜

**Done when:**
- [ ] Route `/leave-calendar`
- [ ] Calendar grid (month/week toggle)
- [ ] Color-coded by leave type
- [ ] Per role: employee = own + colleagues' "out of office"; manager = own + team details; HR = everyone full
- [ ] Click leave bar → see request details
- [ ] Filter by department/office/employee (HR view)
- [ ] Public holidays shown
- [ ] Today highlighted
- [ ] Mobile responsive

**Walid reviews → approves → Foundation Phase done. Next phase = Settings, Compliance modules, Payroll.**

---

## 🎯 Feature Phase 2 (after sidebar features done)

### Feature 9: Settings (Admin) ⬜
Departments / Positions / Offices / Holiday Calendar / User Management / System Settings

### Feature 10: Audit Log Viewer ⬜

### Feature 11: Documents Module ⬜
Per-employee document storage with expiry alerts

### Feature 12: Internal Inbox ⬜

### Feature 13: Announcements ⬜

### Feature 14: All Other Request Types ⬜
Overtime / Expense Claims / Change Shift / Holiday Work Request

### Feature 15: Payroll v1 ⬜ ⚠️ Hardest module
30 golden test cases must pass. Egyptian tax + SI + health insurance + payslip PDF + Excel export.

### Feature 16: Reports ⬜
Standard reports per ANA-3.8 catalog

### Feature 17: Compliance Workflows ⬜
Termination, probation, deemed resignation, retirement workflows

### Feature 18: Government Filings ⬜
NOSI, ETA monthly exports, Form 6 annual

### Feature 19: Foreign Worker Quota Dashboard ⬜
Per Decision/ANA-3.20

### Feature 20: Search (Cmd+K global + per-page filters) ⬜

### Feature 21: Polish ⬜
Empty states, error states, accessibility, keyboard shortcuts, image optimization

---

## 🚀 Phase 2 (after Phase 1 ships)

### Feature 22: Asset module ⬜
### Feature 23: Onboarding/Offboarding workflows ⬜
### Feature 24: AI Assistant ⬜
### Feature 25: Manager Activity Report ⬜
### Feature 26: Multi-language (Arabic + RTL) ⬜
### Feature 27: Flutter mobile app ⬜ (separate codebase)

## 🌟 Phase 3 (SaaS commercialization)

### Feature 28-35: SaaS infrastructure ⬜
Subdomain routing, tenant signup, billing, white-labeling, super-admin tools

---

## 📋 End-of-session ritual

1. Mark task status accurately (⬜ → 🟨 → 🟦 → ✅ or ❌)
2. Add notes under any 🟨 tasks
3. Mark blockers as ❌ with reason
4. Update PROGRESS.md with hours, what got done, lessons learned
5. Commit with conventional commit message

When starting next session:
1. Read this file
2. Find first 🟨 → resume there
3. If none, find first ⬜ → start there
4. If a 🟦 is at top — don't proceed, wait for review

---

## 🆕 Updating this file

This is a living document. When new tasks emerge:
- Add to the bottom of the relevant phase
- Don't reorder already-started tasks
- Note origin: "Added 2026-MM-DD per discussion with Walid about [reason]"

When tasks are split:
- Mark original as ❌ (if obsolete) or ✅ (if wrapper)
- Add the new tasks immediately below it

---

## 📍 Current status

**Phase:** Foundation Phase
**Current task:** F2 (Database setup + foundational migrations) — ⬜ Not started
**Last session:** F1 done (2026-04-29) — Laravel + Inertia + React + TS + Tailwind v4 + lucide-react; Breeze auth scaffold; MySQL `yzh_hr` DB connected; default migrations applied
**Blockers:** None
**Next milestone:** F13 Foundation review checkpoint

**Updated:** 2026-04-29

---

## 🔔 Reminders for Claude Code

1. **Foundation tasks (F1-F13):** Build sequentially, no per-task review. Walid reviews at F13.
2. **Feature tasks (Feature 1+):** STOP after each feature is "done." Walid reviews. Do not proceed without Walid's explicit OK.
3. **Definition of done:** Every checkbox must be ticked.
4. **N-tier discipline:** Every feature passes through Controller → FormRequest → Service → Repository → Model.
5. **Reference docs:** PRD.md, DECISIONS.md, EGYPT_COMPLIANCE_RULES.md, CLAUDE.md, LOCAL_SETUP.md.
6. **When stuck:** Per WHEN_STUCK.md protocol.

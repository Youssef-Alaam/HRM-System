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

### F2: Database setup + foundational migrations ✅

**Goal:** MySQL connected, core tables exist, Eloquent in use.

**Done when:**
- [x] MySQL 8 running locally
- [x] `.env` configured for MySQL connection
- [x] `php artisan migrate` runs cleanly
- [x] Foundational tables created via migrations (in this order):
  - [x] `organizations` (10 cols)
  - [x] `users` (Breeze default + `org_id`, `employee_id`, `deleted_at`)
  - [x] `offices` (lat/long/radius, GPS-ready)
  - [x] `departments` (self-ref `parent_department_id`, `head_employee_id`)
  - [x] `positions` (FK to departments, level)
  - [x] `employees` (54 cols — identity, demographics, contract, leave balances, expat fields, health flags, audit)
  - [x] `holidays` (with `is_make_up` for govt-declared replacements)
  - [x] `audit_logs` (immutable — no `updated_at`)
- [x] All tables include `org_id`, soft deletes (except audit_logs)
- [x] 17 foreign keys correct, including circular `users` ↔ `employees`
- [x] Hot-path indexes on org_id, employment_status, manager/dept/office, hiring_date, is_expat

---

### F3: Auth (Laravel Sanctum) ✅

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

### F4: RBAC via Spatie Permission ✅

**Done when:**
- [ ] `spatie/laravel-permission` installed
- [ ] Migrations run for permission tables
- [ ] `RolePermissionSeeder` created with full permission matrix per PRD §4
- [ ] All 4 roles created
- [ ] Middleware `role:admin`, `permission:edit_employees` usable on routes
- [ ] Tests verify role access

---

### F5: Audit log infrastructure ✅

**Done when:**
- [ ] `Auditable` trait created
- [ ] Observer captures changes (before/after diff as JSON)
- [ ] User, IP, user agent captured on every entry
- [ ] Trait applied to `Employee`, `LeaveRequest`, `AttendanceRecord`, `Holiday`, etc.
- [ ] Test verifies audit_logs entry on create

---

### F6: Multi-tenant scope (org_id everywhere) ✅

**Done when:**
- [ ] `BelongsToOrg` trait created with `OrgScope` global scope
- [ ] Trait auto-sets `org_id` on creating from auth user's org
- [ ] Trait applied to every business model
- [ ] `withoutGlobalScope(OrgScope::class)` works for super-admin operations
- [ ] Test verifies scope isolation

---

### F7: Base layout + sidebar ✅

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

### F8: Seed data ✅

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

### F9: Service + Repository layer scaffolding ✅

**Done when:**
- [x] Folder structure created (Services, Repositories, Contracts, Requests, Resources)
- [x] `BaseRepository` abstract with common methods
- [x] `BaseService` abstract with logging, audit hooks (audit handled by `Auditable` model trait via Eloquent events; BaseService provides `transaction()` so events commit atomically)
- [x] First example feature wired through the layers — Holiday CRUD backend (JSON-only; Feature 9 will add the React pages)
- [x] Documented pattern in CLAUDE.md (with pointers to ARCHITECTURE.md long-form + the Holiday reference vertical)

**Side fix shipped with F9:** `Holiday::date` now persists as `Y-m-d` (not `Y-m-d H:i:s`) so equality checks work in both MySQL (DATE column) and SQLite (test driver). Pattern: any model with a DATE column should use the same `Attribute::make()` accessor — pull into a shared cast class when the second model needs it.

---

### F10: Test framework (Pest) ✅

**Done when:**
- [x] Pest installed (`pestphp/pest:^3.8` + `pest-plugin-laravel:^3.0`). PHPUnit pinned a tick lower (11.5.55 → 11.5.50) to satisfy Pest 3's conflict bound; same minor, no API changes.
- [x] `tests/Feature` and `tests/Unit` directories ready (kept as-is; existing PHPUnit class tests run unchanged via the Pest runner)
- [x] First tests: auth login, RBAC blocks employee from admin route, multi-tenant scope isolates data — covered both by existing PHPUnit suites AND a new Pest-style smoke test ([tests/Feature/Smoke/FoundationSmokeTest.php](tests/Feature/Smoke/FoundationSmokeTest.php)) that demonstrates the canonical Pest convention.
- [x] `php artisan test` runs all tests, all pass — **114 passed (109 PHPUnit + 5 new Pest), 423 assertions, 21s**

**Convention going forward (in CLAUDE.md):** new tests in Pest function syntax. Migrate old PHPUnit files opportunistically when touched — no big-bang rewrite. Helpers + global `uses()` live in [tests/Pest.php](tests/Pest.php).

---

### F11: CI/CD baseline ✅

**Done when:**
- [x] GitHub repo created and pushed (`Youssef-Alaam/HRM-System`, branch `claude/yzh-hr-prototype-prd-xYh5w`)
- [x] [.github/workflows/test.yml](.github/workflows/test.yml) runs on every push and PR
- [x] Workflow runs: ESLint, `tsc --noEmit`, Pest (114 tests)
- [x] Husky 9 + lint-staged pre-commit hook configured ([.husky/pre-commit](.husky/pre-commit), config in [package.json](package.json)). Auto-arms on `npm install` via the `prepare` script.
- [x] First successful CI run — `25127068938` (commit `1070ecd`)

**Side fix shipped with F11:** `tests/TestCase::setUp()` now calls `withoutVite()`. Inertia views call `@vite([...])` in `app.blade.php`, which throws `ViteManifestNotFoundException` when there's no `public/build/manifest.json`. Locally either `npm run dev` is running (Vite serves a hot file) or a stale build provides the manifest, so the bug never surfaced; CI exposed it on the first run. Tests don't render real CSS/JS — `withoutVite()` swaps the facade with a no-op for the duration of every test.

---

### F12: Backup system foundation ✅

**Done when:**
- [x] `spatie/laravel-backup ^9.3` installed
- [x] Backup destination: dedicated `backups` filesystem disk → `storage/app/backups/<APP_NAME>/<timestamp>.zip`
- [x] `php artisan backup:run` creates a successful backup file (verified: 6.65 MB zip with DB dump + project files)
- [x] `php artisan backup:list` shows the backup (1 backup, healthy ✅, reachable ✅)
- [x] Cleanup policy: `keep_all_backups_for_days = 10` (longer-tail weekly/monthly/yearly defaults retained, tune in Phase 2)
- [x] Email notifications stubbed off (channels = `[]`) until Zoho SMTP lands; restore by re-adding `'mail'` channels in `config/backup.php`
- [x] Will be scheduled later when running on prod (Phase 2)

**Side fix shipped with F12:** Windows MySQL installer doesn't add `mysqldump` to PATH. Added `dump.dump_binary_path` env-var pointer to `config/database.php` (`DB_DUMP_BINARY_PATH`); set in local `.env`, documented in `.env.example`. Linux/CI leave it empty.

**.gitignore additions:** `/storage/app/backups`, `/storage/app/backup-temp`.

---

### F12.5: Design Pass 1 ✅

**Goal achieved:** UI lifted from "AI-tier baseline" to a coherent **Engineering Studio** design language across the entire app — title-block strips, drafting-set notation, monospace metadata, gold-as-architectural-annotation, no card grids, no kicker pills, no SaaS clichés.

**Done when (all checked):**
- [x] 6 error pages live; exception routing wired through `bootstrap/app.php` (403/404/419/429/500/503; 500 stays Whoops in debug mode for devs)
- [x] `<Skeleton>` primitive shipped + 2 instance wirings (Dashboard `?loading=1` row skeletons, in-app navigation Compass Arc loading screen)
- [x] `AuthenticatedLayout.tsx` deleted
- [x] Brand-purity grep clean (no `purple-`, `indigo-`, `blue-`, no off-token hex in `resources/` except `#d0a946` in `app.tsx` for Inertia's progress API which now reads `false`)
- [x] `php artisan test` passes — **124 passing, 521 assertions** (up from 114 — added 10 error-page Pest tests)
- [x] TypeScript strict clean
- [x] Walid sign-off on Welcome → Login morph + loading screen + in-app Engineering Studio rollout

**Side fixes shipped beyond the plan (driven by Walid course-corrections):**

1. **Engineering Studio direction adopted** (replaces the original quieter Linear-clone direction). After two rejected mocks of Welcome, Walid picked "Engineering Studio" from a 4-direction reference deck (BIG, Snøhetta, FT Weekend, Coutts, Vitsoe). The architectural-drawing language now defines every surface.
2. **HR wordmark removed** everywhere next to the YZH logo (6 spots: GuestLayout × 2, AppLayout × 3, Welcome × 1) per Walid: "remove the HR from the logo i don't like it keep it just as is for the logo".
3. **Pill+dot kicker pattern banned** everywhere (GuestLayout, Welcome, Dashboard, Placeholder) per Walid: "REMOVE ANYTHING WITH THIS SHAPE IT SCREAMS CLAUDE CODE." Replaced with monospace section identifiers (`A.02 / SIGN IN`).
4. **Welcome → Login split-screen morph** via View Transitions API. Two shared elements morph simultaneously — `brand-panel` (full-screen dark canvas reshapes to left half) + `yzh-hero` (gold YZH word in headline grows into the brand panel's centerpiece wordmark). Desktop only; falls back transparently on mobile and on browsers without the API.
5. **Compass Arc in-app loading screen** (picked from a 4-variant preview: Dimension Line, Frame Draw, Scale Ruler, Compass Arc). Replaces Inertia's default top progress bar. AppLayout subscribes to `router.on('start' / 'finish')` with a 200ms debounce; on tracked routes the destination header swaps in immediately (`B.01 / EMPLOYEES`) and the body shows a 90° gold arc striking, title fading up, gold dot pulsing.
6. **Route registry inside AppLayout** built once from `NAV_GROUPS` + `ADMIN_MENU_ITEMS` — generates drafting-set section identifiers (group letter + position number, e.g. `B.01` for Employees, `S.02` for Audit Log, `P.01` for Profile).
7. **Centralized formatters** in `resources/js/lib/format.ts` per CLAUDE.md "never inline" rule — `formatDate()`, `formatTime()`, `formatDateTime()`, `formatMoneyEgp()` (piasters → EGP), `formatPhoneEg()`, `formatNationalIdEg()`. Dashboard's inline `toLocaleDateString` migrated to `formatDate(new Date())`.
8. **Stamped CTA pattern** (border + uppercase monospace + `ArrowUpRight` + hover-fill) replaces the rounded-md gold button on every guest-layer surface. Inline classes for now; will be extracted to a primitive when a third surface needs it.
9. **Faint gold draftsman grid** background (4% opacity 60px grid lines) on Welcome and GuestLayout brand panel — gives the canvas texture without photography.
10. **Touch-target hardening** — all interactive elements ≥44px on mobile. Sidebar nav items `py-3 lg:py-2` (44px mobile, 36px desktop). Mobile drawer close button `h-10 → h-11`. Welcome top-bar Sign-in CTA `h-10 → h-11`.
11. **GuestLayout brand panel redesigned** as a drafting-set title block (PROJECT HR-001, SECTION A.02, REV) with the giant `YZH` wordmark as the morph landing — coherent with Welcome's aesthetic.

**Test posture at close:** TypeScript strict clean, ESLint clean, Pest 124 passed (521 assertions), em-dashes only in code comments (none in user-facing copy), no `console.log` / `dd()` / `dump()`.

**Reference docs added to the project:** `Components/ErrorShell.tsx`, `Components/LoadingScreen.tsx`, `Components/Skeleton.tsx`, `lib/format.ts`, `Pages/Errors/{403,404,419,429,500,503}.tsx`, `tests/Feature/Errors/ErrorPagesTest.php`.

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

### Feature 4: My Schedule ✅

**Done when:**
- [x] Route `/schedule`
- [x] Today section: shift hours displayed
- [x] Large "Sign In" button if not checked in (stub — "Coming with Attendance (Feature 5)")
- [x] Live HH:MM:SS hours counter stub (–:–:–, wired in Feature 5)
- [x] "Sign Out" button when checked in (stub, Feature 5)
- [x] Weekly calendar with scheduled days
- [x] Date navigation
- [x] Mobile responsive
- [x] Empty state for unscheduled days

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

### Feature 6: My Leave ✅

**Done when:**
- [x] Route `/my-leave`
- [x] Tabs: Pending / Approved / Rejected
- [x] "New Leave Request" form
- [x] leave_type dropdown
- [x] Form shows current balance for selected leave type
- [x] Auto-calculate days excluding public holidays
- [x] Validation: cannot exceed balance, cannot overlap
- [x] Sick leave 3+ calendar days: medical cert upload required (Per Art. 54)
- [x] Study leave: 10-day advance notice enforced (Per Art. 94)
- [x] Cancel pending request works
- [x] Cannot cancel approved without HR override (leave.edit.any gate)
- [x] Decision 13: NULL manager_id auto-approves with balance decrement

---

### Feature 7: Approvals (Manager/HR view) ✅ 2026-05-05

**Done when:**
- [x] Route `/approvals`
- [x] Manager sees pending requests from their team
- [x] HR sees manager-approved + escalations
- [x] Approve button (status, balance decrement, notification)
- [x] Reject button (mandatory reason)
- [x] Per Decision 19: annual = manager discretion; sick/permissions/casual/maternity/paternity = right
- [x] CEO/NULL manager_id auto-approves per Decision 13
- [x] Approver-cascade on terminated approver per ANA-3.18
- [x] Audit log every action

---

### Feature 8: Leave Calendar ✅ 2026-05-05

**Done when:**
- [x] Route `/leave-calendar`
- [x] Calendar grid (month/week toggle)
- [x] Color-coded by leave type
- [x] Per role: employee = own + colleagues' "out of office"; manager = own + team details; HR = everyone full
- [x] Click leave bar → see request details
- [x] Filter by department/office/employee (HR view)
- [x] Public holidays shown
- [x] Today highlighted
- [x] Mobile responsive

**Walid reviews → approves → Foundation Phase done. Next phase = Settings, Compliance modules, Payroll.**

---

## 🎯 Feature Phase 2 (after sidebar features done)

### Feature 9: Settings (Admin) ⬜
Departments / Positions / Offices / Holiday Calendar / User Management / System Settings

### Feature 10: Audit Log Viewer ⬜

### Feature 11: Documents Module 🟨 PROMOTED to Phase 1 (Walid 2026-04-30)
Per-employee document storage with expiry alerts. Required-document matrix per org (7 Egyptian + 3 expat docs locked). HR-only upload — employees never self-upload. See [specs/feature-11-documents.md](specs/feature-11-documents.md). Built right after Feature 2.

### Feature 12: Internal Inbox ⬜

### Feature 13: Announcements ⬜

### Feature 14: All Other Request Types ⬜
Overtime / Expense Claims / Change Shift / Holiday Work Request

### Feature 15: Payroll v1 ❌ DEFERRED (per Walid 2026-04-30)
**Status:** On hold until banking integrations + accountant alignment are ready.
**Reason:** Payroll requires (a) bank-transfer integrations to actually disburse salaries, (b) connections to ~100 modules across the system (attendance, leave, deductions, allowances, EOSB, terminations, expat handling), and (c) sign-off from an Egyptian payroll-specialist accountant on tax brackets / SI rates / health insurance %. Walid is sequencing those upstream first.
**Resumes when:** banking partner selected + accountant onboarded + Walid runs Checkpoint D (golden test case approval).
**Other features proceed without it.** Reports / Compliance Workflows / Government Filings (Features 16-18) build first since they don't depend on payroll output. They later read payroll data once payroll ships.
**Pre-work that can happen now (no banking required):** payslip PDF template design, Excel export schema, golden test case authoring (drafted but not approved), Egyptian tax brackets table seeded into Settings.

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

### Feature 22: Asset module 🟨 PROMOTED to Phase 1 (Walid 2026-04-30)
Per-employee equipment tracking with chain of custody for HR/Admin. Categories: Laptop / Accessories / Phone / Badge ID (no badges yet, category retained). HR can add more categories from Settings. Asset age = snapshot at assignment (Option B). See [specs/feature-22-assets.md](specs/feature-22-assets.md). Built right after Feature 11.
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

**Phase:** Feature Phase 1
**Current task:** Phase 1 sidebar features 1-8 shipped + audited. Feature 5 deferred for browser testing. Next: Checkpoint C — full sidebar walkthrough with Walid.
**Last session (2026-05-05):** Sonnet 4.6 shipped Features 4, 6, 7, 8. Opus 4.7 audit pass: 1 fixup filed (LeaveCalendarRepository → BaseRepository); 3x test runs clean (331 passing, 1759 assertions); browser smoke clean across admin/hr/manager/employee.
**Blockers:** None
**Next milestone:** Checkpoint C — full sidebar walkthrough with Walid (~1 hr). Feature 5 (Attendance) deferred — requires real browser for camera + geolocation.

## 📦 Phase 1 build queue (locked 2026-04-30 across 3 design-discussion rounds)

Working backlog for the next session(s). Each item has a spec at `specs/`. Order matters — earlier items unblock later ones.

1. **Search bar UX fix** — AppLayout's full-page Compass Arc skips same-pathname navigations; Index pages handle their own scoped loading state. (~10 min)
2. **EMP-XXXXX migration** — add `positions.type_code` column, repository code generator, one-off backfill of all 28 seeded employees. Sticky codes per Walid. (~1-2 hr) — see [specs/feature-2-employees.md "Revisions locked 2026-04-30"](specs/feature-2-employees.md)
3. **Sidebar restructure** — Departments/Positions/Offices move to Settings dropdown (Admin only); add Documents (HR/Admin only) + Assets (scoped per role) as top-level sidebar items. (~30 min)
4. **Employee create form + Tier 1 self-edit** — final React form for `/employees/create`; profile-page edit limited to Tier 1 fields (phone, address, emergency contact, marital status, dependents); HR/Admin edits all. (~45 min)
5. **Documents feature (was Feature 11, now Phase 1)** — full vertical: schema (`document_types` + `employee_documents`), N-tier backend, Settings page for required matrix config, employee Documents tab (HR/Admin view), HR dashboard widget for missing/expiring docs, ~25 Pest tests. Required Egyptian docs seeded per Walid's HR list. (~1 day) — see [specs/feature-11-documents.md](specs/feature-11-documents.md)
6. **Assets feature (was Feature 22, now Phase 1)** — full vertical: schema (`asset_categories` + `assets` + `asset_assignments` chain of custody), N-tier backend, sidebar landing with role-scoped data, employee Assets tab (HR/Admin only), Settings page for categories, CSV/Excel export, ~22 Pest tests. (~1 day) — see [specs/feature-22-assets.md](specs/feature-22-assets.md)
7. **Face enrollment flow** — 3-photo wizard via face-api.js; 12-month re-enrollment scheduler; 5-failed-checkin reset; HR/Admin only enrollment authority; PDPL 24h post-termination purge; ~12 Pest tests. (~1 day) — see [specs/face-enrollment.md](specs/face-enrollment.md)
8. **Realistic seed overhaul** — 🟨 PARTIAL (deferred 2026-05-04). Asset distribution per spec (95% laptop / 60% phone / 30% accessories + chain-of-custody demo) already lives in `AssetSeeder`. Profile photos handled frontend via initials avatar — no DB state needed. **Remaining gap:** per-employee `EmployeeDocumentSeeder` creating 4-7 doc rows per employee with intentional 25% partial + 5% incomplete, so the Documents compliance dashboard demos with realistic data instead of empty. Non-blocking — UI uploads work today; revisit before Walid's Checkpoint C walkthrough.
9. **Export buttons (CSV + Excel)** — ✅ Shipped 2026-05-04 in `aac2710`. `maatwebsite/excel ^3.1` installed; three FromCollection exports + one ExportController + `<ExportButtons>` primitive on Employees / Assets / Documents Index pages. 7 new Pest tests; gated `exports.any` (HR + Admin); multi-tenant scoped.

Total: ~5-6 days of focused work to clear the queue.

**Status (2026-05-04):** 8/9 items shipped. Item 8 (doc seeder) deferred as polish.

### Skills installed (locally; see `.agents/skills/`)
impeccable, taste-skill (4), ui-ux-pro-max (8), playwright-skill, obra/superpowers (10), noobygains/godmode (5). Skills are gitignored — they live on this machine, not the repo.

### Toolchain locally (see `C:\Users\Dena\.local\yzh-hr-credentials.txt` for DB creds)
PHP 8.3.30 (winget), Composer 2.9.7, MySQL 8.4.8 (Windows service `MySQL84`), Redis 7.0.15 (in WSL Ubuntu — currently bypassed via `database` driver due to Hyper-V firewall), Node 24.15.0.

**Updated:** 2026-04-30 (after F12.5 close)

---

## 🔔 Reminders for Claude Code

1. **Foundation tasks (F1-F13):** Build sequentially, no per-task review. Walid reviews at F13.
2. **Feature tasks (Feature 1+):** Batched build with 5 named checkpoints (changed 2026-04-30). Run the per-feature internal quality gate on every feature; STOP only at the named checkpoints (Feature 2, mid-Feature 5, end of Feature Phase 1, before/after Payroll). See CLAUDE.md "Build flow" for the full list.
3. **Per-feature internal quality gate:** spec written → Pest tests written first → N-tier built → lint/types/tests clean → brand-purity grep → touch-targets verified → file-size budgets respected → FEATURES_LOG.md entry → demo seed data → CI green.
4. **Payroll v1 (Feature 15) is DEFERRED.** Build everything else; come back to Payroll when banking + accountant are ready. See Feature 15 entry above.
5. **Daily handoff email:** at end of every meaningful build session, run `php artisan handoff:send walid@yzh.solutions` (or his configured email) to send the digest. GitHub Actions cron handles automated 5:30am Cairo morning digests for gym-phone reading.
6. **Definition of done:** Every checkbox must be ticked.
7. **N-tier discipline:** Every feature passes through Controller → FormRequest → Service → Repository → Model.
8. **Reference docs:** PRD.md, DECISIONS.md, EGYPT_COMPLIANCE_RULES.md, CLAUDE.md, LOCAL_SETUP.md.
9. **When stuck:** Per WHEN_STUCK.md protocol.

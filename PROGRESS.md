# YZH-HR — Build Progress Journal

> **End-of-session log.** Walid writes here daily (or every session). This becomes a record of the entire build.
>
> **Why bother:** When you forget what you did 2 weeks ago, this is your time machine. Also useful for milestone reviews and pitch updates.

---

## How to use this file

**At end of every session,** add an entry. Even short ones. Even if you accomplished nothing — note that, and why.

**Template:**

```markdown
## YYYY-MM-DD (Day N)

**Hours:** X
**Phase:** Foundation / Feature 1 / Feature 2 / etc.
**Active task:** F4 (RBAC via Spatie Permission)

### Goal
[What I tried to accomplish today]

### Done
- [Specific things I finished]
- [Tests written]
- [Bugs fixed]

### In progress
- [Things I started but didn't finish]

### Stuck on / blockers
- [Anything blocking me]
- [What stage of WHEN_STUCK.md I'm at]

### Lessons / insights
- [Things I learned today]
- [Patterns to remember]
- [Mistakes to avoid]

### Tomorrow / next session
- [What I'll tackle next]

### Mood / energy
- [Optional, but useful for spotting burnout patterns]
```

---

## 2026-04-28 (Day 0 — Pre-build)

**Hours:** N/A (planning)
**Phase:** Pre-build
**Active task:** Final review cycles + doc generation

### Done
- All 4 review cycles complete (Cycles 1-4)
- Cycle 5 (local-dev readiness) complete
- 28 numbered decisions logged
- Pivoted from Next.js + Supabase + Vercel to Laravel + Inertia + React + MySQL + InMotion VPS
- Decided web-first Phase 1, Flutter mobile Phase 2, SaaS multi-tenant Phase 3
- Generated all knowledge base docs:
  - PRD.md v2 (Laravel-flavored)
  - CLAUDE.md (root, code conventions)
  - EGYPT_COMPLIANCE_RULES.md (article-level law citations)
  - TASK_MANAGER.md (master task list, 13 foundation + 27 features)
  - LOCAL_SETUP.md (Day 0 setup)
  - ARCHITECTURE.md (N-tier patterns)
  - WHEN_STUCK.md (escalation protocol)
  - DECISIONS.md (28 numbered decisions)
  - REVIEW_CHANGELOG.md (full cycle history)
  - TODO.md (daily scratchpad template)
  - PROGRESS.md (this file)

### Lessons / insights
- Pivot to Laravel was the right call once team support became clear
- Web-first reduces Phase 1 complexity significantly (no PWA pain)
- N-tier discipline is non-negotiable for SaaS-readiness
- TASK_MANAGER.md + supervised feature build is the right rhythm

### Next session (Day 1)
- Walid completes LOCAL_SETUP.md (install PHP, MySQL, Redis, etc.)
- Hand off PRD + TASK_MANAGER + CLAUDE.md to Claude Code
- Begin Foundation Phase F1 (project scaffold)
- Foundation tasks F1-F12 proceed sequentially without per-task review
- F13 (Foundation review) is when Walid checks everything

### Mood / energy
Tired but the plan is solid. Time to build.

---

## 2026-04-29 (Day 1)

**Hours:** ~3
**Phase:** Foundation
**Active task:** F1 → ✅ done; next: F2

### Done
- Wiped the deprecated Next.js prototype and committed the 11 planning docs
- Installed Windows toolchain: PHP 8.3.30, Composer 2.9.7, MySQL 8.4.8, Redis 7.0.15 (in WSL Ubuntu), Node 24.15.0
- Created `yzh_hr` MySQL database + app user; credentials saved at `C:\Users\Dena\.local\yzh-hr-credentials.txt` (gitignored location)
- F1: Scaffolded Laravel 11 + Laravel Breeze (Inertia + React + TypeScript) + Tailwind v4 + lucide-react
- Wired `.env` to MySQL; ran default migrations (users, jobs, cache, sessions tables)
- Verified `/`, `/login`, `/register` all serve 200
- Strict TS + Prettier in place

### Caveats / deferred
- **shadcn/ui:** the `npx shadcn init --template laravel` flow created components but its CSS depends on a Tailwind v4 theme config we don't have yet. Removed for now; install per-component when needed via `npx shadcn@latest add <name>`
- **Redis backend:** Hyper-V firewall on Windows 11 blocks PHP processes from connecting to WSL services on `localhost:6379`. WSL Redis is fine internally. Switched `.env` to `database` driver as a working stand-in. Two real fixes for later: install Memurai (native Windows Redis-compat) or add a Hyper-V firewall exception for php.exe.

### Lessons
- Default Laravel installer needs PHP `openssl`, `pdo_mysql`, `mbstring`, `fileinfo`, `curl`, `zip`, `intl`, `bcmath`, `gd` enabled — winget's PHP package ships only the dlls, no `php.ini`. Had to copy `php.ini-development` → `php.ini` and uncomment them.
- WSL2's network bridge is fine for PowerShell-launched TCP but blocked for php.exe by Hyper-V firewall. Surprising; took a while to diagnose.
- shadcn's Laravel template is v4-only and assumes the CSS tokens (`--background`, `--border`, …) become Tailwind utilities via `@theme` mapping — this rewrite of `app.css` is non-trivial.

### Tomorrow / next session
- F2: design and migrate the foundational tables (organizations, users extension, offices, departments, positions, employees, holidays, audit_logs)

---

## 2026-04-29 (Day 1, evening continuation)

**Hours:** ~4
**Phase:** Foundation
**Active task:** F2 + design system + skills install → all ✅; next: F3

### Done
- **Pre-F2 polish** (`e46a3ca`)
  - ESLint v9 flat-config with typescript-eslint, react-hooks, prettier-compat — `npm run lint` runs clean
  - PRODUCT.md committed: brand voice, user-role expectations, anti-references, strategic principles
  - DESIGN.md committed: full token map (colors, type scale, spacing, radius, motion, touch targets, date formats)
- **F2 schema** (`78d1bd5`)
  - 10 migrations across 8 foundational tables — organizations, users (extension), offices, departments, positions, employees (54 cols), holidays, audit_logs
  - 2 deferred-FK migrations to break the circular `users` ↔ `employees` link
  - 17 FKs verified in MySQL after migrate
  - All business tables carry `org_id` + `version` + soft-delete
  - Audit_logs deliberately immutable (no updated_at)
- **Skills installed via `npx skills add`**: impeccable, taste-skill pack, ui-ux-pro-max pack, playwright-skill, obra/superpowers, noobygains/godmode (~71 skills total). All gitignored under `.agents/` and per-agent dirs.
- **Brand & visual layer** (`e9a4957` then `eaa2189`)
  - Color palette extracted from yzhsolutions.com elementor CSS — `#d0a946` primary, ink scale, slate, bone
  - Logos saved (`yzh-mark.png` gold monogram, `yzh-wordmark-white.png`)
  - Tailwind v4 `@theme` carries the YZH tokens
  - Welcome.tsx replaced with branded landing
  - Reskinned all Breeze surfaces: PrimaryButton/SecondaryButton/DangerButton/TextInput/InputLabel/Checkbox/NavLink/ResponsiveNavLink/Dropdown, AuthenticatedLayout (ink topbar, gold-tinted avatar, gold underline on active nav), GuestLayout (split-screen — ink brand panel + bone form panel), ApplicationLogo, all auth pages (Login/Register/ForgotPassword/ResetPassword/VerifyEmail/ConfirmPassword), Dashboard (welcome + "coming up" cards), Profile (Edit + 3 partials including DangerButton modal)
- **Test login seeded**: org "YZH Solutions" + user `test@yzh.test` / `password123`

### Decisions made this session
- **shadcn/ui rolled back** for now — its Laravel template assumes a Tailwind v4 `@theme` token mapping that needs CSS rewriting. Components install per-feature via `npx shadcn@latest add <name>` when actually needed.
- **Redis swapped to database driver** in `.env` — Windows 11 Hyper-V firewall blocks PHP from reaching WSL Redis on `localhost:6379`. WSL Redis works internally. Real fix later: Memurai or Hyper-V firewall exception.
- **Design pass deferred to between F8 and F13.** The current AI-tier UI is functional and on-brand color-wise but lacks character. Polishing now would mean redoing it after F7 (real sidebar per Walid's screenshot) and F8 (seeded data) — both make a real polish pass much more grounded.

### Caveats / open
- Test user has no role assigned yet — F4 will fix
- Dashboard is a placeholder welcome state — real role-aware dashboards land in Feature 1
- No real models written yet (Eloquent) — F9 sets up the Service+Repo scaffolding pattern
- Vite hot-reload background task got stopped at one point; user uses prebuilt assets via `php artisan serve`. Run `npm run dev` if you want hot reload.

### Lessons
- The Tailwind v4 + shadcn + Inertia stack is bleeding-edge enough that even shadcn's official Laravel template needs help. Keep an eye on it when shadcn 3.x lands.
- The `vercel-labs/skills` CLI is the right install path for AI-agent skills. Each repo install needs explicit user authorization — sandbox blocks them as untrusted external code by default.
- Brand color extraction from a WordPress + Elementor site: look for `--e-global-color-*` in the elementor kit CSS (`/wp-content/uploads/elementor/css/post-3.css?ver=*`). That's the locked palette.

### Tomorrow / next session
- F3: install Sanctum, add login rate-limiting (5/15min) + day-lock (10/day), wire login/logout/login_failed events to audit_logs
- Then continue F4 → F8 (user pre-approved chunk)
- After F8: Design Pass 1 (1 newly-added task), then F13 review

<!-- New entries get added below this line -->

---

## 2026-04-29 (Day 1 — Evening continuation)

**Hours:** ~5
**Phase:** Foundation
**Active task:** F3 → F8 + patch round (UX polish, security fixes); ended ready for F9.

### Done
- **F3 (Auth):** Sanctum config + personal_access_tokens migration; `LoginService` (n-tier compliant) holding rate limit, account lock, audit logging. Throttle 5/email+IP/15min decay; auto-lock after 10 failures in 24h. Audit entries for login/login_failed/login_blocked_locked/account_locked/logout/password_reset_*. `last_login_at` + `last_login_ip` captured. Tests: 23 new (was 8).
- **F4 (RBAC):** spatie/laravel-permission v6.25 + middleware aliases (role/permission/role_or_permission). Granular catalog of **71 permissions across 14 domains** (option C from user). Default bundles: admin (71), hr (54), manager (30), employee (17). `RoleDefinitions` is single source of truth; `RolePermissionSeeder` idempotent. `UserPermissionService` wraps Spatie's grant/revoke/reset/assignRole with audit logging. PRD §3 rewritten with the granular catalog. Per-user override verified through middleware.
- **F5 (Audit infrastructure):** `Auditable` trait boots model events (created/updated/deleted/restored/force_deleted), writes "after" on create, "before" on delete, before/after diff on update. No-op suppression. Excludes timestamps + sensitive fields. Created the 7 missing F2 Eloquent models (Organization, Office, Department, Position, Employee, Holiday, AuditLog) with relationships, casts, soft deletes.
- **F6 (Multi-tenant):** `OrgScope` table-qualified global scope, `BelongsToOrg` trait auto-fills org_id from auth. `withoutOrgScope($reason)` is the audited bypass (writes `org_scope_bypass` audit entry). Applied to all 5 tenant-aware models.
- **F7 (Layout):** `AppLayout.tsx` sidebar shell with 9 grouped sections / 19 nav items. Permission-gated nav (frontend filter + backend middleware double-gate). Inertia shared props expose `user.role` + `user.permissions`. `Placeholder.tsx` reused by every sidebar route. Sidebar collapse persists via localStorage with lazy initializer (no flicker). preserveScroll on tab switch. User menu moved to top-right topbar (away from cut-off bottom-of-sidebar dropdown). Profile page switched onto AppLayout.
- **F8 (Seed data):** YZH Solutions org / Avenue Mall office (real lat/lng) / 4 departments / 13 positions / 17 Egyptian 2026 holidays / 30 employees / 4 test users (admin / hr / manager / employee @ yzh.test, password "password"). `migrate:fresh --seed` runs clean and is idempotent.
- **Patches:** show/hide password toggle (5 forms); Inertia progress bar grey → YZH gold; lockout warning at "2 attempts remaining"; daily-failure counter no longer clears on success (security hole closed); password reset rejects reusing the current password (was a bug); register feature removed (internal-only HR system).
- **TINKER_GUIDE.md** added to repo root — full beginner→expert reference (557 lines, 11 sections, copy-paste recipes).

### Tests
- **99 passing** (was 8 at start of session, 343 assertions, ~16s suite)
- New coverage: throttle threshold, account-lock threshold, daily-counter survives success, lockout warning, audit log capture (auth + model writes), permission grant/revoke + audit, multi-tenant scope isolation, route permission gates, register-disabled, password reset rejects reuse, all four test users seeded with correct roles, seeder idempotency.

### In progress
- F9 was started in the last hour (one file: RepositoryInterface.php) but discarded clean before commit so the next session starts fresh from the spec.

### Caveats / open
- LeaveRequest / AttendanceRecord don't have migrations yet — `Auditable` trait will be applied to them when those Feature Phase tables land.
- Custom error pages (403/404/419/429/500/503) deferred to F12.5 Design Pass.
- Skeleton/loading primitive deferred to F12.5 Design Pass.
- 4 manual checks flagged for later (profile edit, multi-tenant smoke across orgs, model write before/after diff demo via tinker, permission grant/revoke via tinker UI). None blocking.
- 12 commits ahead of `origin/claude/yzh-hr-prototype-prd-xYh5w`. **Push is gated on user's explicit go-ahead** (durable preference saved in agent memory).

### Lessons
- Reviewed PRD §3 mid-stream: the user wanted granular per-feature permission toggles like HighLevel's UI. Spatie's per-user grant/revoke supports this natively — only catalog work is needed up-front. Picking option C (granular catalog + per-user override service) before F4 saved a future rename pass across middleware/policies/tests.
- F2 was marked done in a prior session but didn't ship Eloquent models. Caught during F5 because Auditable needs model classes. Corrected silently; recurring lesson: "tables exist" ≠ "feature works."
- The two simultaneous audit rows at the same second (login_failed + account_locked) are correct — single request can write multiple audit rows, and that's a feature for forensics, not a bug.
- Tinker auto-aliases `App\Models\*` thanks to `config/tinker.php`. The `use` statements are still needed for facades and non-model classes.

### Tomorrow / next session
- **F9** — Service + Repository layer scaffolding. Concretely: BaseRepository, BaseService, RepositoryInterface in `app/Repositories/Contracts/`, one example feature (Holiday) wired through Controller → FormRequest → Service → Repository → Resource → Model, ServiceProvider binding interfaces to implementations, tests, CLAUDE.md updated with the canonical "to add a new feature" pattern.
- Then F10 (Pest), F11 (CI/CD — first task that needs `git push` for GitHub Actions to run), F12 (backup), F12.5 (Design Pass with skeleton + custom error pages), F13 (Walid review).

### Mood / energy
Solid sprint. Foundation phase is 8/13 done, and the pieces that remain (F9-F13) are mechanical/polish rather than risk-laden architectural bets.


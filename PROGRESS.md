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

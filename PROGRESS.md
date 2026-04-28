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

<!-- New entries get added below this line -->

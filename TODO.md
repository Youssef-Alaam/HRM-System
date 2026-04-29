# YZH-HR — Daily Scratchpad

> **TASK_MANAGER.md is the master task list.** This file is the daily working scratchpad — the brain dump for THIS session.
>
> **Cleared at end of every session.** Anything important migrates to TASK_MANAGER.md or PROGRESS.md.

**Last session:** 2026-04-29 (evening continuation — F3-F8 + patches, ended ready for F9)

---

## 🎯 Today's focus

F9 — Service + Repository layer scaffolding. Lock in the n-tier pattern so every Feature Phase task copies it instead of inventing it.

---

## 🔥 Right now (active task)

F9 — first up. Nothing in flight from the prior session.

---

## 📋 Next up (this session, after current)

1. F10 — Pest test framework (convert PHPUnit-style tests to Pest, add the plugin)
2. F11 — CI/CD baseline (GitHub Actions workflow + Husky pre-commit). **First foundation task that needs `git push`** — pause for explicit go-ahead before pushing.
3. F12 — Backup foundation (`spatie/laravel-backup`)
4. F12.5 — Design Pass 1 (4-hour timebox, includes skeleton primitive + custom error pages)
5. F13 — Walid review checkpoint

---

## 🤔 Questions for Walid

- None blocking. PRD §3 was rewritten with the granular permission catalog + per-user override semantics during this session — Walid should sanity-read it before F13 in case any role bundle needs adjustment.

---

## 💡 Notes / discoveries

- The `vercel-labs/skills` CLI installs to `.agents/skills/<name>` and symlinks into `.claude/skills/<name>` for Claude Code; gitignored.
- The brand color `#d0a946` was extracted from yzhsolutions.com's elementor kit CSS — `--e-global-color-primary`.
- Tailwind v4 + shadcn don't fully agree yet on Inertia/Laravel projects — install shadcn components per-feature when needed, not eagerly.
- **No skeleton/loading primitive exists.** The earlier Next.js prototype had one (commit `7b91d51`) but it didn't survive the Laravel migration. Captured as a checklist item under F12.5 Design Pass; build it then unless a placeholder feels jarring sooner.
- **Public registration removed (2026-04-29).** YZH HR is internal — accounts created by admin. `/register` returns 404; do not re-enable without a product decision.
- **Custom error pages requested (2026-04-29).** Currently 403/404/500 fall back to Symfony defaults. Branded pages (403/404/419/429/500/503) added to F12.5 Design Pass checklist — render via Inertia so they share app chrome.
- **Tinker auto-aliases `App\Models\*`** via `config/tinker.php` defaults — `User::count()` works without `use` statements. `use` is still needed for facades and non-model classes (e.g. `Cache`, `Hash`, `RateLimiter`, `UserPermissionService`, `RoleDefinitions`).
- F2 was marked complete previously but only shipped migrations, not Eloquent models. Models for Organization/Office/Department/Position/Employee/Holiday/AuditLog were created during F5 because the Auditable trait needed them. Recurring lesson: "tables exist" ≠ "feature works."

---

## 🐛 Bugs found

- None open.

---

## ⏸️ Stuck on

- None.

---

## 🚩 Flagged for later (carry over)

Manual checks queued — none blocking F9, all <5 min each:
- **Profile edit walkthrough** (name change, email change with re-verification, password update, soft-delete account)
- **Multi-tenant smoke** via tinker — create a 2nd org, verify scope isolation between two logged-in users
- **Audit log model-write demo** — create + rename + delete a holiday in tinker, read the before/after diff in `changes`
- **Permission grant/revoke walkthrough** via tinker (UserPermissionService methods + audit-log verification). Will become a real UI under Settings → Users & Roles in Feature Phase.

---

## ✅ Completed this session

(See PROGRESS.md entry for 2026-04-29 evening continuation — F3-F8 + patches + TINKER_GUIDE.md, 99 tests passing.)

---

## 📍 End-of-session checklist

When wrapping up:
- [ ] Update task statuses in TASK_MANAGER.md
- [ ] Migrate "Completed this session" to PROGRESS.md with details
- [ ] Note any new questions for Walid
- [ ] Commit work-in-progress with descriptive message
- [ ] Clear "Right now" and "Next up" sections (set up tomorrow's start)
- [ ] Update "Last session" date at top

---

## 📍 Start-of-session checklist

When starting a new session:
- [ ] Read TASK_MANAGER.md for current state
- [ ] Find first 🟨 (in progress) task — resume there
- [ ] If none, find first ⬜ (not started) task in current phase — start there
- [ ] If a 🟦 (awaiting Walid review) is at top — STOP, wait for review
- [ ] Update "Today's focus" above
- [ ] Begin

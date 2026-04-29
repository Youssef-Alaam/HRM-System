# YZH-HR — Daily Scratchpad

> **TASK_MANAGER.md is the master task list.** This file is the daily working scratchpad — the brain dump for THIS session.
>
> **Cleared at end of every session.** Anything important migrates to TASK_MANAGER.md or PROGRESS.md.

**Last session:** 2026-04-29 (evening)

---

## 🎯 Today's focus

Pick up F3 (Sanctum + login hardening + audit-log auth events) and proceed through the user-pre-approved chunk: F3 → F4 → F5 → F6 → F7 → F8.

---

## 🔥 Right now (active task)

F3 — Sanctum install + rate-limited login + audit_logs entries for login/logout/login_failed/password_reset.

---

## 📋 Next up (this session, after current)

1. F4 — RBAC via Spatie Permission (4 roles, ~30 permissions, middleware, 1-2 example policies)
2. F5 — Audit log infrastructure (`Auditable` trait + observer)
3. F6 — Multi-tenant scope (`BelongsToOrg` trait + `OrgScope` global scope)

---

## 🤔 Questions for Walid

(Things blocking me that need Walid's input)

- None as of 2026-04-29 evening.

---

## 💡 Notes / discoveries

- The `vercel-labs/skills` CLI installs to `.agents/skills/<name>` and symlinks into `.claude/skills/<name>` for Claude Code; gitignored.
- The brand color `#d0a946` was extracted from yzhsolutions.com's elementor kit CSS — `--e-global-color-primary`.
- Tailwind v4 + shadcn don't fully agree yet on Inertia/Laravel projects — install shadcn components per-feature when needed, not eagerly.
- **No skeleton/loading primitive exists.** The earlier Next.js prototype had one (commit `7b91d51`) but it didn't survive the Laravel migration. Captured as a checklist item under F12.5 Design Pass; build it then unless a placeholder feels jarring sooner.
- **Public registration removed (2026-04-29).** YZH HR is internal — accounts created by admin. `/register` returns 404; do not re-enable without a product decision.
- **Custom error pages requested (2026-04-29).** Currently 403/404/500 fall back to Symfony defaults. Branded pages (403/404/419/429/500/503) added to F12.5 Design Pass checklist — render via Inertia so they share app chrome.

---

## 🐛 Bugs found

- None open.

---

## ⏸️ Stuck on

- None.

---

## ✅ Completed this session

(Fully migrated to PROGRESS.md entry for 2026-04-29 evening continuation.)

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

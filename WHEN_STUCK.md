# WHEN STUCK — Escalation Protocol

> When you're stuck on something for more than a few minutes, follow this protocol. Don't just keep banging your head against it. Time-box every layer of escalation.

---

## The 5 stages

### Stage 1: 0-2 hours — solo struggle is normal
Coding has friction. Confusing errors, mismatched docs, that one thing that should work but doesn't. **First 2 hours of struggle is normal.** You're learning.

What to do:
- Read the actual error message carefully (3 times)
- Read the docs for the library you're using
- Try one obvious fix
- Try one less-obvious fix
- Keep notes of what you tried

### Stage 2: 2 hours stuck — `/clear` and start fresh

Claude Code's context fills up after a long session. Sometimes the best move is to wipe context and explain the problem fresh.

What to do:
- Save your work (commit even if broken — `wip:` prefix)
- `/clear` in Claude Code
- Re-read CLAUDE.md, the relevant PRD section, the relevant DECISIONS section
- Restate the problem in your own words to Claude Code
- Often the second attempt finds what the first missed

### Stage 3: 4 hours stuck — try a different AI

Different models have different strengths. If Claude Code is going in circles, sometimes ChatGPT, GitHub Copilot, or Codex finds it instantly.

What to do:
- Open chatgpt.com or another tool
- Paste the error message and the relevant code (sanitize any secrets first)
- Ask: "Why is this happening? What am I missing?"
- Bring fresh insight back to Claude Code

### Stage 4: 6 hours stuck — ask a human community

Real humans on the internet have solved every problem you'll face.

What to do:
- **Laravel community:**
  - r/laravel on Reddit
  - Laravel Discord (laravel.com/discord)
  - Laracasts forum (laracasts.com/discuss)
  - Laravel.io community
- **PHP community:**
  - r/PHP on Reddit
  - PHP Discord
- **General:**
  - Stack Overflow (with `[laravel]`, `[php]`, `[inertiajs]` tags)

Post a clear question:
- What you're trying to do
- What you've tried
- The exact error
- Code snippet (sanitized)
- Stack: Laravel 11 + Inertia + React + MySQL

Communities respond fast for clear questions.

### Stage 5: 1 day stuck — pay for an hour

Senior Laravel devs are cheap by the hour for unblocking.

What to do:
- **Upwork:** search "Laravel developer" — many at $30-100/hr
- **Toptal:** higher quality, $80-200/hr
- **Ask YZH dev team:** they use Laravel, your colleague might unblock you in 15 minutes for free
- **Reddit DMs:** r/forhire or r/laravel sometimes has people offering help
- **Codementor:** quick on-demand video sessions

Often $50 saves you a week.

### Stage 6: 2 days stuck — move on, return later

If a feature is genuinely blocked, don't let it derail the whole project.

What to do:
- Document the blocker in TODO.md with full context
- Mark the task ❌ in TASK_MANAGER.md with reason
- Pick a parallel task from the same tier (one without dependency on the blocked one)
- Return to the blocker in a few days, often the answer comes when you sleep on it

If 1 week passes with the same blocker, **reassess scope.** Maybe the feature isn't worth it. Maybe there's a simpler approach. Talk to Walid.

---

## Common stuck scenarios + fixes

### "TypeScript types don't match between frontend and backend"
- Use Laravel API Resources to define response shape
- Generate TypeScript types from PHP models via [Spatie's TypeScript Transformer](https://github.com/spatie/typescript-transformer)
- Or just keep types in `resources/js/types/` and update manually

### "Inertia page won't update after server response"
- Check that the Controller returns `redirect()` not `Inertia::render()` after a write
- Check that `useForm` is processing the response correctly
- Hard refresh (Cmd+R) to rule out browser cache

### "RBAC permission check returns false unexpectedly"
- Verify role assigned: `php artisan tinker` → `User::find(1)->roles`
- Verify permission seeded: `Role::findByName('admin')->permissions`
- Cache flush: `php artisan cache:clear` (Spatie caches permissions)

### "Migration fails with foreign key error"
- Check migration order (run `php artisan migrate:status`)
- Self-referential FKs (manager_id) need to be added in a separate migration after the table exists
- Drop and re-migrate: `php artisan migrate:fresh --seed`

### "Multi-tenant scope returns empty results"
- Verify `auth()->user()->org_id` is set: `dd(auth()->user())`
- Verify the model uses `BelongsToOrg` trait
- Check the global scope is registered: should see it in `bootBelongsToOrg`
- Test with `withoutGlobalScope`: if data appears, scope is the issue

### "Eloquent query is slow"
- Use `->with()` for eager loading (avoids N+1)
- Add database index on the column being queried
- Use `EXPLAIN` to see query plan
- Cache the result if data is read-heavy

### "Frontend type errors won't go away"
- `npm run type-check` to see all errors at once
- Restart VS Code TypeScript server (Cmd+Shift+P → "TypeScript: Restart TS Server")
- Check `tsconfig.json` includes the file paths you're editing

### "Vite hot reload broken"
- Stop and restart `npm run dev`
- Clear browser cache
- Rebuild: `npm run build` once, then `npm run dev` again

### "Backup/restore failing"
- Check disk space: `df -h`
- Check permissions on `storage/app/backups`: should be 775
- Read `vendor/spatie/laravel-backup/docs/` for known issues

---

## Mental tricks for unblocking

### The rubber duck
Explain the problem out loud to a stuffed animal, plant, or empty chair. The act of articulating forces you to slow down. Often the solution becomes obvious.

### The walk
If you've been at a problem for >2 hours, take a 20-minute walk. Don't think about the problem. Your subconscious works on it. You'll come back with fresh eyes.

### The night sleep
Genuinely. Hard problems often resolve overnight. If it's late and you're stuck, accept the loss for today and try fresh tomorrow.

### The blank slate
Open a fresh file. Write the simplest possible version of what you're trying to do. Just the core. No edge cases. Get THAT working. Then add complexity back one piece at a time.

---

## What NOT to do when stuck

- 🚫 Don't keep trying random fixes for hours hoping one works
- 🚫 Don't rewrite huge sections of code without understanding why the original failed
- 🚫 Don't disable tests to make a feature "ship"
- 🚫 Don't add `try/catch` blocks just to suppress errors
- 🚫 Don't bypass type-checking with `any` to move past TypeScript
- 🚫 Don't disable RBAC to "test if that's the issue"
- 🚫 Don't commit broken code as "WIP" and forget about it
- 🚫 Don't lie to yourself about progress

---

## Tracking stuck-time

Keep a small log in PROGRESS.md when stuck:

```markdown
### Stuck on
- 2026-04-30 14:00 — Inertia auth not persisting across page loads
- Tried: Sanctum config, session driver, Cookie domain
- Stage 2 (cleared context, second attempt failed)
- Moving to Stage 3 (asking ChatGPT)
- 2026-04-30 16:30 — RESOLVED: missing `SESSION_DOMAIN=localhost` in .env

### Lesson
- Sanctum needs both SANCTUM_STATEFUL_DOMAINS and SESSION_DOMAIN set explicitly
```

This becomes valuable later — when you hit the same problem in 3 months, you'll thank past-you.

---

## Escalation flowchart

```
Problem hits
    ↓
[Stage 1] 0-2 hrs — try standard fixes
    ↓ (still stuck)
[Stage 2] /clear Claude Code, fresh attempt
    ↓ (still stuck)
[Stage 3] Ask another AI
    ↓ (still stuck)
[Stage 4] Post in community, browse Stack Overflow
    ↓ (still stuck)
[Stage 5] Pay for senior dev hour OR ask YZH team
    ↓ (still stuck)
[Stage 6] Document blocker, switch tasks, return in days
    ↓ (1 week stuck)
Reassess scope with Walid. Talk it through.
```

Most problems resolve at Stage 1 or 2. Stage 5 is rare. Don't be afraid to spend $50.

# Feature 4 — My Schedule

## Status
⬜ Not started

## PRD reference
- PRD §8 Feature 4
- TASK_MANAGER Feature 4 done-when checklist

## User story
Any authenticated user with `attendance.view.own` visits `/schedule` and sees their workweek:
- Today's shift hours (from `employees.shift_start_time` + `shift_end_time`)
- A "Sign In" stub button — visually disabled with copy "Coming with Attendance (Feature 5)"
- A live hours-counter stub rendered as static "–:–:–" (wired in Feature 5)
- A weekly grid of 7 columns (Sun → Sat) showing scheduled workdays and public holidays
- Prev / next week navigation that navigates without unmounting the page

## Routes
- `GET /schedule` — replaces the `placeholder.schedules` route.
  Permission gate: `attendance.view.own`.
- Week selected via `?week=YYYY-MM-DD` (the Sunday of the target week; defaults to current week's Sunday).

## Data shape

Server returns:

```ts
type ScheduleDay = {
    date: string;           // ISO YYYY-MM-DD
    day_name: string;       // 'Sun' | 'Mon' | 'Tue' | 'Wed' | 'Thu' | 'Fri' | 'Sat'
    is_workday: boolean;
    is_today: boolean;
    holiday: { id: number; name: string } | null;
};

type Props = {
    employee: {
        shift_start:    string | null;  // 'HH:MM' 24-hour, e.g. '09:00'
        shift_end:      string | null;
        workweek_days:  string[];       // e.g. ['sun','mon','tue','wed','thu']
    } | null;
    week_start: string;    // ISO YYYY-MM-DD — Sunday of displayed week
    week_end:   string;    // ISO YYYY-MM-DD — Saturday of displayed week
    days:       ScheduleDay[];  // exactly 7 elements
    prev_week:  string;    // ISO YYYY-MM-DD — Sunday of prev week
    next_week:  string;    // ISO YYYY-MM-DD — Sunday of next week
};
```

## Edge cases
- **User without employee record** — `employee` prop is `null`; page shows "No schedule configured" empty state, no crash.
- **No workweek_days set** — service defaults to `['sun','mon','tue','wed','thu']` (Egyptian convention per CLAUDE.md).
- **Holiday on a workday** — `is_workday: true`, `holiday` non-null. Both markers coexist.
- **Holiday on a day-off** — `is_workday: false`, `holiday` non-null. Day still shows the holiday strip.
- **Multi-tenant isolation** — holidays filtered by `auth()->user()->org_id` via `OrgScope`.
- **Invalid `?week` param** — silently falls back to current week.

## Acceptance criteria
- [ ] Route `/schedule` wired, replaces `placeholder.schedules`
- [ ] Permission gate `attendance.view.own` on route
- [ ] `ScheduleController::index()` delegates to `ScheduleService`
- [ ] `ScheduleService::getWeekData(User, ?string): array` returns the above shape
- [ ] `ScheduleRepository` abstracts Employee + Holiday queries, extends `BaseRepository`
- [ ] `ScheduleRepositoryInterface` in `app/Repositories/Contracts/`
- [ ] Bound in `AppServiceProvider::REPOSITORY_BINDINGS`
- [ ] "Sign In" renders as `disabled` CTA with copy "Coming with Attendance (Feature 5)"
- [ ] Hours counter renders as static "–:–:–" stub
- [ ] 7-column grid Sun-Sat; workweek days visually distinct; today's column with thicker border
- [ ] Holidays shown in gold strip inside their day column
- [ ] Prev/next via `router.get` with `replace: true, preserveState: true`
- [ ] Mobile responsive (horizontal scroll on the week grid, no reflow)
- [ ] Multi-tenant: only the org's holidays appear; Pest test verifies this

## Out of scope (this slice)
- Real check-in/out (Feature 5)
- Live HH:MM:SS counter (Feature 5)
- Team schedule view (manager seeing team schedules)
- Shift assignment/editing (Feature 9)

## Compliance citations
None. Read-only display of shift configuration.

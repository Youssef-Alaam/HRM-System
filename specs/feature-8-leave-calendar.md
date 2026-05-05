# Feature 8: Leave Calendar

## Purpose
Give all roles a visual monthly/weekly calendar showing who is on leave and when,
with public holidays highlighted. Replaces the "Out of office" lookup.

## Routes
| Method | URI | Permission |
|--------|-----|------------|
| GET | `/leave-calendar` | `leave.view.own` (all roles have this) |

Query params: `?view=month|week` (default: month), `?year=YYYY&month=MM` (month view),
`?year=YYYY&week=WW` (week view), `?department_id=N&office_id=N&employee_id=N` (HR only).

## Data visibility per role
| Role | Own leave | Colleagues |
|------|-----------|------------|
| employee | Full detail (type + reason) | "Out of office" label only (name + dates, no type/reason) |
| manager | Full detail | Team = full detail; others = "Out of office" only |
| hr / admin | Full detail for everyone | Full detail for everyone |

## Calendar entries
### Leave entries
Each entry has:
- `employee_id`, `employee_name`
- `leave_type_code`, `leave_type_name` (null → "Out of office" for restricted visibility)
- `start_date`, `end_date`, `days_count`
- `status`: only `approved` entries shown (no pending)

### Holiday entries
- `date`, `name`
- Shown as full-width gold strip across the day/week

## Controller: `LeaveCalendarController`
- Single `index()` method
- Inertia render `LeaveCalendar/Index`

## Service: `LeaveCalendarService`
- `getData(User $user, array $filters): array`
  - Resolves current period (month or week)
  - Calls `LeaveCalendarRepository::entriesForPeriod()`
  - Calls `LeaveCalendarRepository::holidaysForPeriod()`
  - Applies visibility filter based on role
  - Returns: `{ view, year, month, week, days[], entries[], holidays[], filters? }`

## Repository: `LeaveCalendarRepository` (new, interface + binding)
- `entriesForPeriod(int $orgId, string $from, string $to, array $employeeIds = []): Collection`
  - Returns approved leave requests in range with employee + leave_type
- `holidaysForPeriod(int $orgId, string $from, string $to): Collection`
- `teamEmployeeIds(int $managerEmployeeId): array` — direct reports
- `allEmployeeIds(int $orgId): array` — HR: everyone

## Inertia Props
```ts
type CalendarEntry = {
  id: number;
  employee_id: number;
  employee_name: string;
  leave_type_code: string | null;   // null = restricted visibility
  leave_type_name: string | null;
  leave_type_color: string;         // CSS class suffix
  start_date: string;               // Y-m-d
  end_date: string;
  days_count: number;
};

type HolidayEntry = {
  date: string;  // Y-m-d
  name: string;
};

type DayCell = {
  date: string;       // Y-m-d
  is_today: boolean;
  is_weekend: boolean;
};

type Props = {
  view: 'month' | 'week';
  year: number;
  month: number;            // 1-12
  week: number | null;      // ISO week
  week_start: string;       // Y-m-d (always set)
  week_end: string;
  days: DayCell[];
  entries: CalendarEntry[];
  holidays: HolidayEntry[];
  role: 'employee' | 'manager' | 'hr';
};
```

## Leave type color mapping (CSS classes)
- annual → yzh-gold (border + text)
- sick → slate
- casual → slate  
- maternity/paternity → teal (use `text-teal-600 border-teal-300`)
- study → `text-amber-600 border-amber-300`
- default/out-of-office → yzh-bone-soft border with yzh-slate text

## Month view grid
- 7-column grid (Sun–Sat), standard month calendar with leading/trailing empty cells
- Each day cell: date number, holiday strip (gold), stacked leave bars

## Week view
- 7 columns for the selected week
- Wider cells, show more details per entry

## Toggles + navigation
- Month/week toggle: `?view=week` vs `?view=month`
- Prev/next: `?year=&month=` (month view) or `?year=&week=` (week view)
- Today button

## HR filter bar
- Department select, office select, employee typeahead — only shown when `role = 'hr'`
- Filters applied via query params

## Mobile
- Horizontal scroll on the 7-column grid (`overflow-x-auto`)

## Tests (Pest)
- Guest redirect
- Employee can access
- Employee sees own approved leave, not others' type/reason
- Employee does NOT see pending leave
- Manager sees team full detail
- HR sees all approved leave
- Holidays appear in response
- Month view generates correct day count (28/30/31)
- Week view generates 7 days
- HR filter by department returns only that dept's entries
- Cross-org isolation

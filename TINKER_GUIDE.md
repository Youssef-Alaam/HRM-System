# Tinker Guide — beginner to expert

> A practical reference for using `php artisan tinker` against the YZH-HR app.
> Read top-to-bottom the first time. After that, jump to the section you need.
>
> **Tinker = an interactive PHP REPL** that boots the full Laravel app — same
> database, same models, same services, same auth — and lets you run any PHP
> against it. Think of it as a backend "browser DevTools console."
>
> **Tinker = your single best testing & debugging tool until the admin UI ships.**

---

## 0. Setup

### Starting tinker

```bash
php artisan tinker
```

If `php` isn't on your PATH (Windows winget install), use the full path:

```powershell
& "C:\Users\Dena\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe" artisan tinker
```

### Exiting

- `exit` then Enter
- or **Ctrl+D**

### What tinker is NOT

- Not a sandbox. Writes commit immediately.
- Not separate from your app — it shares the dev MySQL connection.
- Not a transaction. Closing tinker doesn't roll anything back.

---

## 1. Beginner — read-only sanity checks

These commands only READ data. Safe to run anywhere.

### Aliases (typing-savers, no DB hit)

> **Heads up:** Laravel's tinker config auto-aliases everything under
> `App\Models\*`, so `User::count();` works WITHOUT a `use` statement
> the moment you start tinker. You only need `use` for facades and for
> classes outside `App\Models\` (services, custom helpers, constants).

Paste at the start of every tinker session — these are the ones that
aren't auto-aliased:

```php
use App\Permissions\RoleDefinitions;
use App\Services\Permissions\UserPermissionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
```

(The model `use` statements below are optional but harmless — keeps the
guide self-contained if you copy snippets out of context.)

```php
use App\Models\User;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\Department;
use App\Models\Position;
use App\Models\Office;
use App\Models\Holiday;
use App\Models\AuditLog;
```

### Counting rows

```php
User::count();           // 4 (test users)
Employee::count();       // 30
Organization::count();   // 1
Holiday::count();        // 17
AuditLog::count();       // grows as you click around
```

### Listing names / emails / fields

```php
User::pluck('email');                                  // collection of emails
User::pluck('email', 'id');                            // map: id → email
Employee::pluck('first_name')->take(5);                // first 5 names
Department::pluck('name');
Holiday::pluck('name', 'date')->take(5);
```

### Finding a single row

```php
User::find(1);                                         // by primary key
User::where('email', 'admin@yzh.test')->first();       // first match
User::where('email', 'admin@yzh.test')->firstOrFail(); // throws if missing
```

### Filtering and counting

```php
Employee::where('employment_status', 'active')->count();
Employee::where('is_expat', true)->count();
Employee::whereNotNull('manager_id')->count();          // employees with a boss
Holiday::whereYear('date', 2026)->count();
```

### Inspecting a row's columns

```php
$u = User::where('email', 'admin@yzh.test')->first();
$u->toArray();           // pretty-printed as an associative array
$u->getAttributes();     // raw column values (no relationships)
```

### Pretty-printing collections

```php
User::all()->toArray();
dump(Employee::take(3)->get()->toArray());     // dump() = forced pretty print
```

---

## 2. Intermediate — relationships and reads through joins

### Loading relationships

```php
$u = User::with('organization')->find(1);
$u->organization->name;                          // → "YZH Solutions"

$emp = Employee::with(['department', 'position', 'office'])->first();
$emp->department->name;
$emp->position->title;
$emp->office->name;
$emp->manager?->first_name;                      // null-safe if no manager
```

### Walking the org chart

```php
$mgr = Employee::whereHas('directReports')->first();   // anyone with reports
$mgr->directReports->pluck('first_name');
$mgr->directReports->count();
```

### Eager-loading to avoid N+1

```php
$emps = Employee::with('manager')->take(10)->get();
$emps->each(fn ($e) => print($e->first_name . ' → ' . ($e->manager?->first_name ?? 'no manager') . "\n"));
```

---

## 3. Multi-tenant scope (very important to understand)

Most business models (Employee, Holiday, Office, Department, Position) auto-filter by `auth()->user()->org_id` via `OrgScope`.

### Without an authenticated user — no scope applied

```php
auth()->logout();                                // make sure no one is logged in
Holiday::count();                                // → 17 (returns everything)
```

### Acting as a user — scope kicks in

```php
$admin = User::where('email','admin@yzh.test')->first();
auth()->login($admin);
Holiday::count();                                // → 17 (only YZH Solutions; same here since 1 org)

// Create another org to see scope isolation in action:
$orgB = Organization::create(['name' => 'Test Org B']);
auth()->logout();
Holiday::create(['org_id' => $orgB->id, 'date' => '2026-12-25', 'name' => 'B-only']);
Holiday::count();                                // → 18 (no scope, sees both)

auth()->login($admin);
Holiday::count();                                // → 17 (scoped to admin's org)
```

### Bypass the scope (audited)

```php
Holiday::withoutOrgScope('debugging cross-tenant')->count();   // → 18 — bypasses scope
AuditLog::where('action', 'org_scope_bypass')->latest()->first();   // → row with reason
```

> Use `withoutOrgScope($reason)` instead of raw `withoutGlobalScope` so the
> bypass shows up in audit_logs. The reason argument is required.

### Cleanup after testing

```php
Holiday::where('org_id', $orgB->id)->forceDelete();
$orgB->forceDelete();
```

---

## 4. Audit log — your single pane of glass

Every model write (and every auth event) lands in `audit_logs` with: actor, IP, user-agent, action, before/after diff.

### Latest events across the system

```php
AuditLog::latest()->take(20)->get(['action','user_id','ip_address','created_at']);
```

### One user's auth history

```php
$u = User::where('email','employee@yzh.test')->first();
AuditLog::where('user_id', $u->id)
    ->whereIn('action', [
        'login', 'logout', 'login_failed',
        'login_blocked_locked', 'account_locked',
        'password_reset_link_requested', 'password_reset_completed',
        'password_reset_rejected_reuse',
    ])
    ->latest()
    ->get(['action','ip_address','created_at']);
```

### Failed logins for forensics

```php
AuditLog::where('action','login_failed')
    ->where('created_at','>', now()->subDay())
    ->latest()
    ->get(['ip_address','user_agent','changes','created_at']);
```

### Track everything that happened to a specific entity

```php
AuditLog::where('entity_type', Holiday::class)
    ->where('entity_id', '5')
    ->latest()
    ->get(['action','user_id','changes','created_at']);
```

### Reading the `changes` column (the diff)

```php
$row = AuditLog::where('action','updated')->latest()->first();
$row->changes;                       // ['before' => [...], 'after' => [...]]
$row->changes['before']['name'] ?? null;
$row->changes['after']['name'] ?? null;
```

### Counting by action

```php
AuditLog::selectRaw('action, count(*) as n')->groupBy('action')->get();
```

### Catalog of action strings you'll see

| action | when it fires |
|---|---|
| `created` | any Auditable model is inserted |
| `updated` | columns change (with before/after diff) |
| `deleted` | soft delete |
| `restored` | soft-deleted row is restored |
| `force_deleted` | hard delete |
| `login` | successful login |
| `logout` | user logs out |
| `login_failed` | wrong password / unknown email |
| `login_blocked_locked` | tried to log into a locked account |
| `account_locked` | the 10/24h trigger fired |
| `password_reset_link_requested` | user asked for reset email |
| `password_reset_completed` | new password set successfully |
| `password_reset_rejected_reuse` | tried to reset to current password |
| `password_reset_failed` | bad token / expired link |
| `permission_granted` | UserPermissionService::grant |
| `permission_revoked` | UserPermissionService::revoke |
| `permissions_reset_to_role_defaults` | UserPermissionService::resetToRoleDefaults |
| `role_assigned` | UserPermissionService::assignRole |
| `org_scope_bypass` | Model::withoutOrgScope('reason') |

---

## 5. Account lockout — the manual unlock playbook

### See if a user is locked

```php
$u = User::where('email','employee@yzh.test')->first();
$u->locked_at;       // null = unlocked, timestamp = locked
$u->lock_reason;
```

### Inspect the daily-failure counter (in cache)

```php
Cache::get("login.failures.daily.{$u->id}");   // null or an int 1..10
```

### Manually unlock (your point #3)

```php
$u->update(['locked_at' => null, 'lock_reason' => null]);
Cache::forget("login.failures.daily.{$u->id}");        // also clear the counter
```

User can log in immediately after.

### Manually lock (for testing only — never in real use)

```php
$u->update(['locked_at' => now(), 'lock_reason' => 'manual test lock']);
```

### Reset the per-IP throttle (the 5/15min one)

```php
use Illuminate\Support\Facades\RateLimiter;
RateLimiter::clear('employee@yzh.test|127.0.0.1');     // exact email|IP key
```

---

## 6. Permission grant/revoke (per-user overrides)

### Show current state

```php
$mgr = User::where('email','manager@yzh.test')->first();
$mgr->getRoleNames();                          // ['manager']
$mgr->getAllPermissions()->pluck('name');     // role + direct grants
$mgr->permissions->pluck('name');             // direct grants only
$mgr->can('payroll.run');                     // false (manager role doesn't have it)
$mgr->can('chat.send');                       // true (role default)
```

### Grant a permission this user wouldn't normally have

Acting as admin matters — the audit log captures who granted:

```php
$svc = app(UserPermissionService::class);
$admin = User::where('email','admin@yzh.test')->first();
auth()->login($admin);

$svc->grant($mgr, 'payroll.run', 'Q2 coverage while HR is on leave');
$mgr->fresh()->can('payroll.run');             // true
```

### Revoke (removes a direct grant; does NOT block role-inherited)

```php
$svc->revoke($mgr, 'payroll.run', 'coverage period ended');
$mgr->fresh()->can('payroll.run');             // false again
```

### Reset back to role defaults

```php
$svc->grant($mgr, 'audit.view', 'temp');
$svc->grant($mgr, 'payroll.run', 'temp');
$svc->resetToRoleDefaults($mgr, 'cleanup after audit');
$mgr->fresh()->permissions->count();           // 0 — back to role defaults
```

### Change a user's role outright

```php
$emp = User::where('email','employee@yzh.test')->first();
$svc->assignRole($emp, RoleDefinitions::ROLE_HR, 'Promoted to HR Specialist');
$emp->fresh()->getRoleNames();                 // ['hr']
```

### Verify the audit trail

```php
AuditLog::whereIn('action', ['permission_granted','permission_revoked','role_assigned','permissions_reset_to_role_defaults'])
    ->with('user')                              // the actor
    ->latest()
    ->take(10)
    ->get(['action','user_id','entity_id','changes','created_at']);
```

> All the above will eventually be a UI under Settings → Users & Roles. The
> backend is the same path either way — UI just calls UserPermissionService.

---

## 7. Useful tricks

### See the SQL queries Laravel runs

```php
DB::listen(fn ($q) => print($q->sql . ' [' . implode(',', $q->bindings) . ']' . "\n"));
Employee::where('is_expat', false)->take(3)->get();
// → prints the actual SQL with bindings
```

### Compare passwords manually

```php
$u = User::where('email','admin@yzh.test')->first();
Hash::check('password', $u->password);         // true
Hash::check('wrongguess', $u->password);       // false
```

### Force-set a password (e.g. forgotten test password)

```php
$u->forceFill(['password' => Hash::make('newpassword123')])->save();
```

### Verify a user's email in tests

```php
$u->forceFill(['email_verified_at' => now()])->save();
```

### Date math with Carbon (every Laravel timestamp is one)

```php
$u->created_at;
$u->created_at->diffForHumans();                          // "3 hours ago"
$u->created_at->format('d M Y H:i');                      // "29 Apr 2026 13:42"
now()->subDays(7);
AuditLog::where('created_at', '>', now()->subHour())->count();
```

### Run an artisan command from inside tinker

```php
\Illuminate\Support\Facades\Artisan::call('migrate:status');
echo \Illuminate\Support\Facades\Artisan::output();
```

### Resolve a service container binding

```php
$svc = app(UserPermissionService::class);      // gets the configured instance
```

---

## 8. Real test scenarios — copy-paste recipes

### Recipe A — Simulate the 10-fails-in-24h lockout from scratch

```php
$u = User::where('email','employee@yzh.test')->first();
Cache::put("login.failures.daily.{$u->id}", 9, 86400);   // pretend 9 fails happened

// Now in the browser, log in once with the wrong password — 10th fails, lock fires.

$u->refresh()->locked_at;                                // not null
AuditLog::where('action','account_locked')->latest()->first();
```

### Recipe B — Reset everything for a test user

```php
$u = User::where('email','employee@yzh.test')->first();
$u->update(['locked_at' => null, 'lock_reason' => null, 'last_login_at' => null, 'last_login_ip' => null]);
Cache::forget("login.failures.daily.{$u->id}");
RateLimiter::clear("employee@yzh.test|127.0.0.1");
```

### Recipe C — Wipe audit log for a clean re-test (DESTRUCTIVE)

```php
AuditLog::truncate();           // ⚠ irreversible. Don't run on prod data.
```

### Recipe D — Find which user did the last thing

```php
AuditLog::with('user')->latest()->first();
```

### Recipe E — Watch live as you click around (run, then click in browser)

```php
DB::listen(fn ($q) => print(now()->format('H:i:s') . ' ' . $q->sql . "\n"));
```

Type `Ctrl+D` to stop watching.

---

## 9. Things NOT to do in tinker

| Don't | Why |
|---|---|
| `User::truncate()` | empties the table; FK cascade can wreck more than you think |
| `User::forceDelete()` on a model in scope | hard-deletes; bypasses soft-delete safety |
| `DB::statement('DROP TABLE …')` | obvious, but tempting in panic |
| Editing `audit_logs` rows | the whole point is they're immutable |
| `auth()->loginUsingId(N)` then forgetting to logout | the session leak only matters in tinker, but it can confuse subsequent tests |
| Running tinker against production | tinker doesn't know dev vs prod — it uses whatever `.env` is loaded |

---

## 10. Quick-reference cheat sheet

| What you want | Command |
|---|---|
| Count rows | `Model::count()` |
| Find by id | `Model::find(1)` |
| Find by column | `Model::where('col','val')->first()` |
| All values of one column | `Model::pluck('col')` |
| Pretty print | `dump($x)` |
| Latest N | `Model::latest()->take(5)->get()` |
| Update | `$row->update(['col' => 'val'])` |
| Soft delete | `$row->delete()` |
| Restore | `$row->restore()` |
| Hard delete | `$row->forceDelete()` |
| Login as user | `auth()->login($user)` |
| Logout | `auth()->logout()` |
| Bypass org scope | `Model::withoutOrgScope('reason')` |
| Run SQL directly | `DB::select('SELECT …')` |
| See queries | `DB::listen(fn($q)=>dump($q->sql))` |
| Hash a password | `Hash::make('plain')` |
| Check a password | `Hash::check('plain', $hash)` |
| Quick datetime | `now()`, `now()->subDays(7)` |
| Cache get/put | `Cache::get('key')`, `Cache::put('key','val',3600)` |
| Forget cache | `Cache::forget('key')` |

---

## 11. Practice path — first → fluent

If you're learning, run these in order. Each step assumes the aliases from §1.

1. `User::count();` — confirm DB connection works
2. `User::pluck('email');` — see the four test users
3. `$u = User::where('email','admin@yzh.test')->first();` — bind a variable
4. `$u->toArray();` — see all the columns
5. `$u->organization;` — follow a relationship
6. `$u->getAllPermissions()->pluck('name');` — see permissions for that user
7. `AuditLog::latest()->take(5)->get(['action','user_id']);` — read recent events
8. Open a second terminal and log into the app in your browser. Re-run #7 — you'll see new rows.
9. `Cache::get("login.failures.daily.{$u->id}");` — peek at the lock counter
10. Try Recipe A above — simulate a lockout and verify in the audit log
11. Try Recipe B — reset everything cleanly
12. Try the permission grant/revoke flow in §6

By #12 you'll have used 90% of what you'll ever need.

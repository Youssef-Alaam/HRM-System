# YZH-HR — Architecture Reference

> **N-tier layered architecture for Laravel + Inertia + React.**
> When implementing a feature, follow this structure exactly. Don't shortcut.

---

## The 6 layers

```
1. Presentation (React/Inertia pages)
2. Controller (HTTP entry point)
3. Validation (FormRequest)
4. Service (business logic)
5. Repository (data access)
6. Model (Eloquent + DB)
```

---

## Why this matters

- **Easier testing:** Service layer is testable without mocking HTTP
- **Easier change:** Swap database without touching business logic
- **Easier teams:** Frontend devs work in React, backend in PHP, both have clear contracts
- **Easier scaling to SaaS:** Clean layers = clean refactor to multi-tenant
- **Easier debugging:** Bug in business logic? Look in Services. Bug in DB? Repositories. Bug in UI? React components.

---

## Layer 1: Presentation (React + Inertia)

**Where:** `resources/js/Pages/` and `resources/js/Components/`

**Responsibilities:**
- Render UI
- Handle user interactions
- Submit forms via Inertia's `useForm`
- Display errors from server response

**NOT responsibilities:**
- Business logic
- Direct database queries
- Validation that needs to be authoritative (always also validate server-side)

**Example:**

```tsx
// resources/js/Pages/Employees/Create.tsx
import { useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

export default function CreateEmployee() {
  const { data, setData, post, processing, errors } = useForm({
    first_name: '',
    last_name: '',
    email: '',
    national_id: '',
  });

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    post(route('employees.store'));
  };

  return (
    <form onSubmit={submit} className="space-y-4">
      <div>
        <Label htmlFor="first_name">First Name</Label>
        <Input
          id="first_name"
          value={data.first_name}
          onChange={(e) => setData('first_name', e.target.value)}
        />
        {errors.first_name && <p className="text-red-600 text-sm mt-1">{errors.first_name}</p>}
      </div>
      <Button type="submit" disabled={processing}>Create Employee</Button>
    </form>
  );
}
```

---

## Layer 2: Controller

**Where:** `app/Http/Controllers/`

**Responsibilities:**
- Receive HTTP request
- Delegate to FormRequest for validation
- Call Service layer for work
- Return Inertia response or JSON

**NOT responsibilities:**
- Business logic (delegate to Service)
- Direct database queries (delegate to Repository via Service)
- Complex data transformation (use Resource classes or DTOs)

**File size limit:** 200 lines.

**Example:**

```php
// app/Http/Controllers/EmployeeController.php
namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Services\EmployeeService;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    public function __construct(
        private EmployeeService $service
    ) {}

    public function index()
    {
        $employees = $this->service->list();

        return Inertia::render('Employees/Index', [
            'employees' => EmployeeResource::collection($employees),
        ]);
    }

    public function store(StoreEmployeeRequest $request)
    {
        $employee = $this->service->create($request->validated());

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Employee created successfully.');
    }

    public function update(UpdateEmployeeRequest $request, int $id)
    {
        $this->service->update($id, $request->validated());

        return redirect()
            ->route('employees.show', $id)
            ->with('success', 'Employee updated.');
    }

    public function destroy(int $id)
    {
        $this->service->softDelete($id);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee deleted.');
    }
}
```

Notice: controller is **thin**. No business logic. Just receive → validate → delegate → respond.

---

## Layer 3: FormRequest (Validation + Authorization)

**Where:** `app/Http/Requests/`

**Responsibilities:**
- Authorization (return true if user can perform action)
- Validation rules
- Custom error messages
- Optional: prepare data before validation

**Example:**

```php
// app/Http/Requests/StoreEmployeeRequest.php
namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Employee::class);
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:employees,email'],
            'national_id' => [
                'required',
                'regex:/^\d{14}$/',
                'unique:employees,national_id',
            ],
            'phone' => ['required', 'regex:/^01[0125]\d{8}$/'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'hiring_date' => ['required', 'date'],
            'position_id' => ['required', 'exists:positions,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'office_id' => ['required', 'exists:offices,id'],
            'manager_id' => ['nullable', 'exists:employees,id'],
            'contract_type' => ['required', 'in:probation,fixed,unlimited,part_time,internship,project'],
            'base_salary_egp' => ['required', 'numeric', 'min:7000'],
            'is_expat' => ['boolean'],
            'passport_number' => ['required_if:is_expat,true'],
            'work_permit_expiry' => ['required_if:is_expat,true', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'national_id.regex' => 'National ID must be exactly 14 digits.',
            'phone.regex' => 'Phone must be a valid Egyptian mobile number.',
            'base_salary_egp.min' => 'Salary cannot be below minimum wage of EGP 7,000.',
        ];
    }
}
```

---

## Layer 4: Service (Business Logic)

**Where:** `app/Services/`

**Responsibilities:**
- All business logic
- Wrap multi-step operations in DB transactions
- Call Repositories for data access
- Fire Events
- Return DTOs or domain objects

**NOT responsibilities:**
- Direct Eloquent calls (delegate to Repository)
- HTTP-specific code (no Request handling)
- Frontend concerns

**File size limit:** 150 lines per service. Extract sub-services if larger.

**Example:**

```php
// app/Services/EmployeeService.php
namespace App\Services;

use App\Events\EmployeeCreated;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\LeaveService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeService
{
    public function __construct(
        private EmployeeRepositoryInterface $employees,
        private UserRepositoryInterface $users,
        private LeaveService $leaveService,
    ) {}

    public function list(array $filters = [])
    {
        return $this->employees->paginate($filters);
    }

    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            $user = $this->users->create([
                'name' => $data['first_name'] . ' ' . $data['last_name'],
                'email' => $data['email'],
                'password' => Hash::make(str()->random(16)),
                'org_id' => auth()->user()->org_id,
            ]);

            $employee = $this->employees->create([
                ...$data,
                'user_id' => $user->id,
            ]);

            $user->assignRole('employee');

            $this->leaveService->initializeBalances($employee);

            event(new EmployeeCreated($employee));

            return $employee;
        });
    }

    public function update(int $id, array $data): Employee
    {
        $employee = $this->employees->find($id);

        if (isset($data['version']) && $data['version'] !== $employee->version) {
            throw new \App\Exceptions\ConcurrencyException(
                'This record was updated by another user. Please reload.'
            );
        }

        $this->employees->update($employee, $data);

        return $employee->fresh();
    }

    public function softDelete(int $id): void
    {
        $employee = $this->employees->find($id);
        $this->employees->delete($employee);

        app(RequestRoutingService::class)->cascadeApprovalsFromTerminated($employee);
    }
}
```

---

## Layer 5: Repository (Data Access)

**Where:** `app/Repositories/` (implementations) and `app/Repositories/Contracts/` (interfaces)

**Responsibilities:**
- All Eloquent queries
- Caching strategies (if any)
- Query optimization (eager loading, indexing hints)

**NOT responsibilities:**
- Business rules
- Validation
- HTTP concerns

**Pattern:**

```php
// app/Repositories/Contracts/EmployeeRepositoryInterface.php
namespace App\Repositories\Contracts;

use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EmployeeRepositoryInterface
{
    public function find(int $id): Employee;
    public function findByEmail(string $email): ?Employee;
    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator;
    public function create(array $data): Employee;
    public function update(Employee $employee, array $data): bool;
    public function delete(Employee $employee): bool;
    public function countByStatus(): array;
}
```

```php
// app/Repositories/EmployeeRepository.php
namespace App\Repositories;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    public function find(int $id): Employee
    {
        return Employee::with(['position', 'department', 'office', 'manager'])
            ->findOrFail($id);
    }

    public function findByEmail(string $email): ?Employee
    {
        return Employee::where('email', $email)->first();
    }

    public function paginate(array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = Employee::with(['position', 'department']);

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('first_name', 'like', "%{$filters['search']}%")
                  ->orWhere('last_name', 'like', "%{$filters['search']}%")
                  ->orWhere('email', 'like', "%{$filters['search']}%")
                  ->orWhere('employee_code', 'like', "%{$filters['search']}%")
                  ->orWhere('national_id', 'like', "%{$filters['search']}%");
            });
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['employment_status'])) {
            $query->where('employment_status', $filters['employment_status']);
        }

        return $query->paginate($perPage);
    }

    public function create(array $data): Employee
    {
        return Employee::create($data);
    }

    public function update(Employee $employee, array $data): bool
    {
        return $employee->update($data);
    }

    public function delete(Employee $employee): bool
    {
        return $employee->delete();
    }

    public function countByStatus(): array
    {
        return Employee::selectRaw('employment_status, COUNT(*) as count')
            ->groupBy('employment_status')
            ->pluck('count', 'employment_status')
            ->toArray();
    }
}
```

### Binding interface to implementation

In `app/Providers/AppServiceProvider.php`:

```php
public function register(): void
{
    $this->app->bind(
        \App\Repositories\Contracts\EmployeeRepositoryInterface::class,
        \App\Repositories\EmployeeRepository::class
    );
}
```

---

## Layer 6: Model

**Where:** `app/Models/`

**Responsibilities:**
- Define table relationships
- Scopes (BelongsToOrg, etc.)
- Mutators / accessors for data formatting
- Observers for events (via traits)

**NOT responsibilities:**
- Business logic
- Validation (use FormRequest)
- Complex queries (use Repository)

**File size limit:** 250 lines.

```php
// app/Models/Employee.php (abbreviated)
namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToOrg;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes, BelongsToOrg, Auditable;

    protected $fillable = [/* 40+ fields */];

    protected $casts = [
        'date_of_birth' => 'date',
        'hiring_date' => 'date',
        'is_expat' => 'boolean',
        'workweek_days' => 'array',
        'base_salary_piasters' => 'integer',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function position(): BelongsTo { return $this->belongsTo(Position::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function office(): BelongsTo { return $this->belongsTo(Office::class); }
    public function manager(): BelongsTo { return $this->belongsTo(Employee::class, 'manager_id'); }
    public function subordinates() { return $this->hasMany(Employee::class, 'manager_id'); }

    protected function fullName(): Attribute
    {
        return Attribute::get(
            fn () => "{$this->first_name} {$this->last_name}"
        );
    }
}
```

NO business logic. Just data shape, relationships, basic accessors.

---

## How a request flows through all 6 layers

User submits a "Create Employee" form:

1. User clicks "Create" in `Create.tsx` → `useForm.post(route('employees.store'))`
2. Browser sends POST `/employees` with form data + CSRF token
3. Laravel routes to `EmployeeController::store(StoreEmployeeRequest)`
4. Laravel auto-runs `StoreEmployeeRequest`: `authorize()` → `rules()`. Invalid → 422 with errors → React shows them.
5. `EmployeeController::store` calls `$this->service->create($validated)`
6. `EmployeeService::create`: `DB::transaction` → `UserRepository::create()` → `EmployeeRepository::create()` → `BelongsToOrg` trait auto-sets `org_id` → `Auditable` observer creates audit_log → `assignRole('employee')` → `LeaveService::initializeBalances()` → commit → `EmployeeCreated` event
7. Listeners run (welcome email queued, etc.)
8. Returns `Employee` model to controller
9. Controller redirects to `/employees/{id}` with success flash
10. Inertia receives redirect, renders `Show.tsx` with new employee data

Each layer does one job. Clean.

---

## Where business logic ABSOLUTELY must NOT go

🚫 **Never put business logic in:**
- **Controllers** — should be 5-15 lines per method
- **Models** — should be data + relationships
- **Repositories** — queries only
- **FormRequests** — validate, not decide
- **React components** — display, not compute business outcomes
- **Migrations** — describe schema, not seed business data

🟢 **Business logic ONLY belongs in:**
- **Services** (primarily)
- **Jobs** (when async)
- **Events/Listeners** (when reactive)
- **Console Commands** (CLI operations)

---

## Repeating the rule for emphasis

**Every feature follows this 6-layer pattern.**

If you find yourself writing business logic in a controller, STOP. Extract to a service. Even tiny features benefit. Consistency matters more than convenience.

When stuck on where something belongs, ask: "If I needed to test this without HTTP, where would the logic be?" That's the layer it belongs in.

---

## Common patterns

### Pattern 1: Read with caching

```php
// Repository
public function getActiveEmployeesCount(): int
{
    return Cache::remember(
        "active_employees_count_org_{$this->getCurrentOrgId()}",
        now()->addMinute(),
        fn () => Employee::where('employment_status', 'active')->count()
    );
}
```

### Pattern 2: Multi-step write

```php
// Service
public function approveLeave(LeaveRequest $request, User $approver, string $comment): LeaveRequest
{
    return DB::transaction(function () use ($request, $approver, $comment) {
        $this->leaves->update($request, [
            'status' => 'approved',
            'hr_id' => $approver->id,
            'hr_decided_at' => now(),
            'hr_comment' => $comment,
        ]);

        $this->balanceService->decrement(
            $request->employee_id,
            $request->leave_type,
            $request->days_count
        );

        event(new LeaveRequestApproved($request));

        return $request->fresh();
    });
}
```

### Pattern 3: Heavy calculation

```php
// Service that delegates to a focused calculator
public function calculateMonthlyPayroll(Employee $employee, int $year, int $month): array
{
    $base = $employee->base_salary_piasters;
    $tax = app(IncomeTaxCalculator::class)->forMonth($base, $year, $month);
    $si = app(SocialInsuranceCalculator::class)->forMonth($base, $year, $month);
    return compact('base', 'tax', 'si');
}
```

Each calculator class is small (under 150 lines) and singularly focused. Tested independently.

---

## Tests organized by layer

```
tests/
├── Unit/                          // pure functions, no HTTP
│   ├── Services/
│   │   ├── EmployeeServiceTest.php
│   │   ├── LeaveServiceTest.php
│   │   └── PayrollServiceTest.php
│   ├── Calculators/
│   │   ├── IncomeTaxCalculatorTest.php
│   │   └── SocialInsuranceCalculatorTest.php
│   └── Helpers/
│       └── MoneyHelperTest.php
└── Feature/                       // full HTTP request cycle
    ├── Auth/
    │   └── LoginTest.php
    ├── Employees/
    │   ├── CreateEmployeeTest.php
    │   ├── EditEmployeeTest.php
    │   └── DeleteEmployeeTest.php
    ├── Attendance/
    │   └── CheckInTest.php
    └── Permissions/
        └── RoleAccessTest.php
```

---

## Final principle

**Architecture is a contract.** When everyone follows it, the codebase is predictable. When someone shortcuts it, technical debt compounds. For a project that will:
- Grow from internal tool to SaaS
- Possibly involve hiring more developers
- Need to last 10+ years
- Handle sensitive HR data

…the discipline of following N-tier rigorously is what separates a successful product from a broken one.

When in doubt, take the longer path. Future you (and future Walid) will thank you.

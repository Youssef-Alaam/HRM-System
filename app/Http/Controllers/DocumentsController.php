<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use App\Models\Employee;
use App\Repositories\Contracts\DocumentTypeRepositoryInterface;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Sidebar landing for HR + Admin (Pages/Documents/Index). Lists every
 * employee's compliance status against the required-doc matrix and
 * surfaces the all-org expiring + expired buckets.
 */
class DocumentsController extends Controller
{
    public function __construct(
        private readonly EmployeeDocumentRepositoryInterface $repo,
        private readonly DocumentTypeRepositoryInterface $types,
    ) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('documents.view.any'), 403);

        $orgId = $request->user()->org_id;
        $documentTypes = $this->types->activeForOrg($orgId);

        $employees = Employee::query()
            ->where('org_id', $orgId)
            ->whereNull('deleted_at')
            ->orderBy('first_name')
            ->get([
                'id', 'employee_code', 'first_name', 'last_name',
                'is_expat', 'gender', 'employment_status',
            ]);

        $compliance = $employees->map(function (Employee $employee) use ($documentTypes) {
            $required = $documentTypes
                ->filter(fn (DocumentType $t) => $t->is_required && $t->appliesTo($employee));

            $uploaded = $this->repo->forEmployee($employee->id);
            $uploadedTypeIds = $uploaded->pluck('document_type_id')->filter()->unique();

            $missing = $required
                ->filter(fn (DocumentType $t) => ! $uploadedTypeIds->contains($t->id))
                ->values();
            $expired = $uploaded
                ->filter(fn ($d) => $d->isExpired())
                ->values();
            $expiringSoon = $uploaded
                ->filter(fn ($d) => $d->isExpiringWithin(30))
                ->values();

            return [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'is_expat' => (bool) $employee->is_expat,
                'employment_status' => $employee->employment_status,
                'required_count' => $required->count(),
                'uploaded_count' => $uploadedTypeIds->count(),
                'missing_count' => $missing->count(),
                'missing' => $missing->map(fn (DocumentType $t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                ])->all(),
                'expiring_soon_count' => $expiringSoon->count(),
                'expired_count' => $expired->count(),
            ];
        });

        return Inertia::render('Documents/Index', [
            'employees' => $compliance->values()->all(),
            'totals' => [
                'employees' => $compliance->count(),
                'missing_any' => $compliance->where('missing_count', '>', 0)->count(),
                'expiring_30d' => $compliance->sum('expiring_soon_count'),
                'expired' => $compliance->sum('expired_count'),
            ],
        ]);
    }

    /**
     * Snapshot used by the HR/Admin dashboard compliance widget.
     *
     * @return array<string, int>
     */
    public static function complianceSnapshot(
        int $orgId,
        DocumentTypeRepositoryInterface $types,
        EmployeeDocumentRepositoryInterface $documents,
    ): array {
        $documentTypes = $types->activeForOrg($orgId);

        $employees = Employee::query()
            ->where('org_id', $orgId)
            ->whereNull('deleted_at')
            ->where('employment_status', 'active')
            ->get(['id', 'is_expat', 'gender']);

        $missingAny = 0;
        $expiringIn30 = 0;
        $expired = 0;
        $now = CarbonImmutable::now();

        foreach ($employees as $employee) {
            $required = $documentTypes
                ->filter(fn (DocumentType $t) => $t->is_required && $t->appliesTo($employee));
            $uploaded = $documents->forEmployee($employee->id);
            $uploadedTypeIds = $uploaded->pluck('document_type_id')->filter()->unique();
            $missing = $required->filter(fn (DocumentType $t) => ! $uploadedTypeIds->contains($t->id));
            if ($missing->count() > 0) {
                $missingAny++;
            }
            foreach ($uploaded as $doc) {
                if ($doc->isExpired($now)) {
                    $expired++;
                } elseif ($doc->isExpiringWithin(30, $now)) {
                    $expiringIn30++;
                }
            }
        }

        return [
            'missing_any' => $missingAny,
            'expiring_in_30_days' => $expiringIn30,
            'expired' => $expired,
        ];
    }
}

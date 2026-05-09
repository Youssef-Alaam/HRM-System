<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo data seeder for EmployeeDocument so the Documents compliance dashboard
 * has realistic state to render at Walid's Checkpoint C walkthrough.
 *
 * Distribution per the locked queue spec (Phase 1 item 8):
 *   - 70% complete       — every required doc on file, valid expiry
 *   - 25% partial        — at least one required doc missing
 *    - 5% incomplete      — multiple required docs missing
 *
 * Plus a sprinkle of expiring soon (≤60 days) and expired docs to exercise
 * the expiry alert UI on /reports and /documents.
 *
 * No actual files written — file_path points to a stub path. The list view
 * doesn't fetch the binary, so this is fine for demo purposes. HR re-uploads
 * via the UI before going to production.
 */
class EmployeeDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::query()->where('name', 'YZH Solutions')->firstOrFail();

        $hr = User::query()
            ->where('org_id', $org->id)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['hr', 'admin']))
            ->first();

        if (! $hr) {
            $this->command?->warn('No HR/admin user found — skipping EmployeeDocumentSeeder.');
            return;
        }

        $employees = Employee::query()
            ->where('org_id', $org->id)
            ->where('employment_status', 'active')
            ->get();

        if ($employees->isEmpty()) {
            $this->command?->warn('No active employees — skipping EmployeeDocumentSeeder.');
            return;
        }

        // Pre-load all required doc types per applies_to bucket
        $allRequired = DocumentType::query()
            ->where('org_id', $org->id)
            ->where('is_required', true)
            ->where('is_active', true)
            ->get();

        $count = $employees->count();
        $partialThreshold = (int) ceil($count * 0.30);   // 5% incomplete + 25% partial = 30%
        $incompleteThreshold = (int) ceil($count * 0.05); // first 5% are incomplete

        $employees = $employees->shuffle()->values();

        foreach ($employees as $i => $employee) {
            $applicable = $allRequired->filter(function (DocumentType $t) use ($employee) {
                if ($t->applies_to === DocumentType::APPLIES_ALL) {
                    return true;
                }
                if ($t->applies_to === DocumentType::APPLIES_EGYPTIAN) {
                    return ! $employee->is_expat;
                }
                if ($t->applies_to === DocumentType::APPLIES_EXPAT) {
                    return $employee->is_expat;
                }
                return false;
            })->values();

            $bucket = match (true) {
                $i < $incompleteThreshold => 'incomplete',
                $i < $partialThreshold => 'partial',
                default => 'complete',
            };

            $skipCount = match ($bucket) {
                'incomplete' => max(2, (int) ceil($applicable->count() * 0.5)),
                'partial' => 1,
                default => 0,
            };

            $skipIndices = $skipCount > 0
                ? $applicable->keys()->shuffle()->take($skipCount)->all()
                : [];

            foreach ($applicable as $idx => $type) {
                if (in_array($idx, $skipIndices, true)) {
                    continue;
                }

                $expiryStrategy = $this->expiryStrategy($idx, $employee->id);

                EmployeeDocument::create([
                    'org_id' => $org->id,
                    'employee_id' => $employee->id,
                    'document_type_id' => $type->id,
                    'file_path' => "demo/employees/{$employee->id}/type-{$type->id}.pdf",
                    'original_filename' => str($type->name)->slug().'.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size_bytes' => random_int(50_000, 900_000),
                    'issued_date' => now()->subMonths(random_int(1, 36))->toDateString(),
                    'expiry_date' => $type->default_expiry_months === null
                        ? null
                        : $expiryStrategy,
                    'notes' => null,
                    'uploaded_by_user_id' => $hr->id,
                    'uploaded_at' => now()->subDays(random_int(1, 90)),
                ]);
            }
        }

        $this->command?->info("Seeded employee documents: incomplete={$incompleteThreshold}, partial bucket up to {$partialThreshold}, rest complete.");
    }

    /**
     * Mix of valid, expiring-soon, and expired dates so the alerts surface.
     * Deterministic-ish via employee_id to keep re-runs stable per employee.
     */
    private function expiryStrategy(int $typeIdx, int $employeeId): string
    {
        $bucket = ($typeIdx + $employeeId) % 10;

        return match (true) {
            $bucket === 0 => now()->subDays(random_int(1, 30))->toDateString(),  // 10% expired
            $bucket <= 2 => now()->addDays(random_int(1, 60))->toDateString(),   // 20% expiring soon
            default      => now()->addMonths(random_int(6, 36))->toDateString(), // 70% safe
        };
    }
}

<?php

use App\Support\PositionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-off backfill: rewrite every employee's employee_code to the new
 * EMP-XXXXX format (per the 2026-04-30 design lock with Walid). Walks
 * employees grouped by org + position type_code, ordered by hiring_date
 * ASC tiebreak id ASC so the new tenure sequence reflects who was hired
 * first within each type bucket. Soft-deleted rows are included in the
 * sequence so codes stay sticky and slots aren't reused.
 *
 * The previous YZH-{org}-{NNNN} codes are overwritten in place. The down()
 * is a no-op — there's no path back to the old format and the new format
 * is the long-term contract.
 */
return new class extends Migration
{
    public function up(): void
    {
        $orgIds = DB::table('employees')
            ->select('org_id')
            ->distinct()
            ->pluck('org_id');

        foreach ($orgIds as $orgId) {
            $this->backfillOrg((int) $orgId);
        }
    }

    public function down(): void
    {
        // Intentionally no-op. EMP-XXXXX is the new long-term contract;
        // rolling back would only re-derive synthetic YZH-{org}-NNNN codes
        // and risk colliding with codes issued after this migration.
    }

    private function backfillOrg(int $orgId): void
    {
        // Pull every employee in this org (with soft-deleted) joined to
        // their position so we can read type_code in a single sweep.
        $rows = DB::table('employees as e')
            ->leftJoin('positions as p', 'p.id', '=', 'e.position_id')
            ->where('e.org_id', $orgId)
            ->orderBy('e.hiring_date')
            ->orderBy('e.id')
            ->get(['e.id', 'e.position_id', 'p.type_code']);

        // Counters per type bucket. Each row consumes the next slot.
        $sequenceByType = [];

        foreach ($rows as $row) {
            $typeCode = $row->type_code !== null
                ? (int) $row->type_code
                : PositionType::OTHER;

            $next = ($sequenceByType[$typeCode] ?? 0) + 1;
            $sequenceByType[$typeCode] = $next;

            DB::table('employees')
                ->where('id', $row->id)
                ->update([
                    'employee_code' => sprintf('EMP-%d%04d', $typeCode, $next),
                ]);
        }
    }
};

<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\FaceEnrollmentService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * PDPL purge: wipes face descriptors + photos for any employee whose
 * `deleted_at` was set 24+ hours ago. Runs daily; idempotent (already-
 * purged employees no-op because their descriptor is already null).
 *
 * Schedule:
 *   $schedule->command('face:purge-terminated')->dailyAt('03:00');
 * (Wired in routes/console.php so it runs alongside the existing
 * scheduler tasks.)
 */
class PurgeFaceDataForTerminatedCommand extends Command
{
    protected $signature = 'face:purge-terminated';

    protected $description = 'Wipe face descriptors + photos for employees terminated 24+ hours ago (PDPL).';

    public function handle(FaceEnrollmentService $service): int
    {
        $cutoff = CarbonImmutable::now()->subHours(FaceEnrollmentService::POST_TERMINATION_PURGE_HOURS);

        $candidates = Employee::query()
            ->onlyTrashed()
            ->whereNotNull('deleted_at')
            ->where('deleted_at', '<=', $cutoff)
            ->whereNotNull('face_descriptor')
            ->get();

        $purged = 0;
        foreach ($candidates as $employee) {
            $service->purgeForTerminatedEmployee($employee);
            $this->line("Purged face data for employee #{$employee->id} ({$employee->employee_code}).");
            $purged++;
        }

        $this->info("Done — {$purged} employee(s) purged.");

        return self::SUCCESS;
    }
}

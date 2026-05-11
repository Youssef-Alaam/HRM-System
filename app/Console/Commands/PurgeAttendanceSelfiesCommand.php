<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Services\AttendanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * PDPL compliance — purge attendance selfies 24h after capture.
 * Per Decision 7: face descriptor and reference photo are retained;
 * the check-in selfie itself is deleted.
 *
 * Schedule (routes/console.php):
 *   $schedule->command('attendance:purge-selfies')->hourly();
 */
class PurgeAttendanceSelfiesCommand extends Command
{
    protected $signature = 'attendance:purge-selfies {--dry-run : Only report what would be deleted}';

    protected $description = 'Delete attendance selfies older than 24h (PDPL — Decision 7).';

    public function handle(AttendanceRepositoryInterface $repo, AttendanceService $service): int
    {
        $cutoff = now()->subHours(24);
        $records = $repo->selfiesDueForPurge($cutoff);

        if ($records->isEmpty()) {
            $this->info('No selfies due for purge.');

            return self::SUCCESS;
        }

        $count = 0;

        foreach ($records as $record) {
            if ($this->option('dry-run')) {
                $this->line("  Would purge: {$record->id} ({$record->selfie_path})");
                $count++;

                continue;
            }

            if ($record->selfie_path && Storage::disk('local')->exists($record->selfie_path)) {
                Storage::disk('local')->delete($record->selfie_path);
            }

            $service->markSelfiePurged($record);
            $count++;
        }

        $verb = $this->option('dry-run') ? 'Would have purged' : 'Purged';
        $this->info("{$verb} {$count} selfies.");

        return self::SUCCESS;
    }
}

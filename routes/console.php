<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Daily handoff digest
|--------------------------------------------------------------------------
| Walid reads progress on his phone at the gym. The 5:30 AM Cairo digest
| summarizes the previous 24h of git activity + current task + test status.
| Only fires when HANDOFF_RECIPIENT is set in .env (no recipient = no send).
|
| In production this runs via GitHub Actions cron (the workflow file calls
| `php artisan schedule:run`). Locally on Walid's laptop it requires Windows
| Task Scheduler to fire `php artisan schedule:run` every minute.
*/
Schedule::command('handoff:send --since=24h')
    ->timezone('Africa/Cairo')
    ->dailyAt('05:30')
    ->when(fn () => filled(config('mail.handoff_recipient')))
    ->onFailure(function () {
        // Don't crash the scheduler if mail fails — log and move on.
        logger()->warning('Daily handoff digest failed to send');
    });

/*
|--------------------------------------------------------------------------
| PDPL face-data purge
|--------------------------------------------------------------------------
| 24h after employees.deleted_at is set, wipe descriptors + photos.
| Runs daily at 03:00 Cairo. Idempotent — already-purged rows no-op.
*/
Schedule::command('face:purge-terminated')
    ->timezone('Africa/Cairo')
    ->dailyAt('03:00');

/*
|--------------------------------------------------------------------------
| Attendance selfie purge (Decision 7 / PDPL)
|--------------------------------------------------------------------------
| Check-in selfies are retained 24h then auto-deleted. Reference photos
| (the enrollment photo set) are kept for re-comparison. The descriptor
| math match score is logged on the attendance_records row regardless.
*/
Schedule::command('attendance:purge-selfies')
    ->timezone('Africa/Cairo')
    ->hourly();

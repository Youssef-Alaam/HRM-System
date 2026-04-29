<?php

namespace App\Console\Commands;

use App\Mail\HandoffMail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Process\Process;

/**
 * php artisan handoff:send
 *
 * Sends a build-progress digest to Walid's email so he can read on his phone
 * at the gym. Pulls fresh data each run: latest commits, current test count,
 * touched files, current task per TASK_MANAGER.md.
 *
 * Recipient resolution: --email arg > HANDOFF_RECIPIENT env var > fail.
 *
 * Wired to schedule:run via routes/console.php for the 5:30am Cairo daily
 * digest. Also runnable on-demand at end of any build session.
 */
class HandoffSendCommand extends Command
{
    protected $signature = 'handoff:send
                            {--email= : Override the HANDOFF_RECIPIENT env var}
                            {--since=24h : Time window for "recent" data (24h, 7d, 1w)}
                            {--dry-run : Print the digest to stdout instead of sending}';

    protected $description = 'Email the daily build digest to Walid';

    public function handle(): int
    {
        $recipient = $this->option('email') ?: config('mail.handoff_recipient');

        if (! $recipient && ! $this->option('dry-run')) {
            $this->error('No recipient — pass --email=name@example.com or set HANDOFF_RECIPIENT in .env');

            return self::FAILURE;
        }

        $digest = $this->buildDigest();

        if ($this->option('dry-run')) {
            $this->info('--- HANDOFF DIGEST (dry run) ---');
            $this->line("Subject: {$digest['subject']}");
            $this->newLine();
            $this->line("Phase:  {$digest['phase']}");
            $this->line("Task:   {$digest['current_task']}");
            $this->newLine();
            $this->line('Recent commits:');
            foreach ($digest['commits'] as $c) {
                $this->line("  {$c['hash']} {$c['date']}  {$c['subject']}");
            }
            $this->newLine();
            $this->line("Tests:        {$digest['tests']}");
            $this->line("Branch:       {$digest['branch']}");
            $this->line("Window:       {$digest['since']}");

            return self::SUCCESS;
        }

        Mail::to($recipient)->send(new HandoffMail($digest));

        $this->info("Digest sent to {$recipient}");

        return self::SUCCESS;
    }

    /**
     * Assemble the digest payload from git, the file system, and the
     * project's docs. Pure data — no formatting decisions live here.
     *
     * @return array<string, mixed>
     */
    private function buildDigest(): array
    {
        $since = $this->resolveSince($this->option('since'));
        $today = Carbon::now('Africa/Cairo')->format('D, d M Y');

        return [
            'subject' => "YZH HR / {$today} / build digest",
            'date' => $today,
            'recipient_name' => 'Walid',
            'phase' => $this->extractCurrentPhase(),
            'current_task' => $this->extractCurrentTask(),
            'branch' => $this->git(['rev-parse', '--abbrev-ref', 'HEAD']) ?: 'unknown',
            'commits' => $this->recentCommits($since),
            'tests' => $this->latestTestCount(),
            'since' => $this->option('since'),
        ];
    }

    private function resolveSince(string $window): string
    {
        // Accept '24h', '7d', '1w' — pass through to git's --since syntax.
        return match (true) {
            str_ends_with($window, 'h') => $window.' ago',
            str_ends_with($window, 'd') => $window.'ays ago',
            str_ends_with($window, 'w') => $window.'eeks ago',
            default => '24 hours ago',
        };
    }

    /**
     * @return list<array{hash: string, date: string, subject: string}>
     */
    private function recentCommits(string $since): array
    {
        // Pipe separator — Symfony Process passes args as-is (no shell), so
        // \t escapes wouldn't interpolate. `|` is unambiguous in commit messages.
        $output = $this->git([
            'log',
            "--since={$since}",
            '--pretty=format:%h|%ad|%s',
            '--date=format:%a %d %b %H:%M',
            '-n', '20',
        ]);

        if (! $output) {
            return [];
        }

        $commits = [];
        foreach (explode("\n", $output) as $line) {
            [$hash, $date, $subject] = array_pad(explode('|', $line, 3), 3, '');
            $hash = trim($hash);
            if ($hash !== '') {
                $commits[] = [
                    'hash' => $hash,
                    'date' => trim($date),
                    'subject' => trim($subject),
                ];
            }
        }

        return $commits;
    }

    private function git(array $args): ?string
    {
        $process = new Process(array_merge(['git'], $args), base_path());
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        return trim($process->getOutput());
    }

    /**
     * Read the most recent line of the Pest test summary from storage if
     * present; otherwise omit the count from the digest.
     */
    private function latestTestCount(): string
    {
        $cache = storage_path('app/last-test-count.txt');
        if (file_exists($cache)) {
            $content = trim(file_get_contents($cache) ?: '');
            if ($content) {
                return $content;
            }
        }

        return 'not recorded — run `php artisan test | tail -3 > storage/app/last-test-count.txt`';
    }

    private function extractCurrentPhase(): string
    {
        $content = $this->safeFileGet(base_path('TASK_MANAGER.md'));
        if (! $content) {
            return 'unknown';
        }
        if (preg_match('/^\*\*Phase:\*\*\s*(.+)$/m', $content, $m)) {
            return trim($m[1]);
        }

        return 'unknown';
    }

    private function extractCurrentTask(): string
    {
        $content = $this->safeFileGet(base_path('TASK_MANAGER.md'));
        if (! $content) {
            return 'unknown';
        }
        if (preg_match('/^\*\*Current task:\*\*\s*(.+)$/m', $content, $m)) {
            return trim($m[1]);
        }

        return 'unknown';
    }

    private function safeFileGet(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }
        $content = @file_get_contents($path);

        return $content === false ? null : $content;
    }
}

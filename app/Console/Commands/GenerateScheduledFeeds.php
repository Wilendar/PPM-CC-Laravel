<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Export\FeedSchedulerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * GenerateScheduledFeeds - Artisan command for scheduled feed generation.
 *
 * Checks all active ExportProfiles with non-manual schedules and dispatches
 * GenerateFeedJob for those that are due (based on next_generation_at).
 *
 * Runs every 15 minutes via Laravel Scheduler with withoutOverlapping()
 * to prevent duplicate dispatches.
 *
 * Usage:
 *   php artisan feeds:generate-scheduled            # Normal run
 *   php artisan feeds:generate-scheduled --dry-run   # Preview only
 *
 * @package App\Console\Commands
 * @since ETAP_07f - Export & Feed System
 */
class GenerateScheduledFeeds extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'feeds:generate-scheduled
                            {--dry-run : Only show which feeds would be generated, without dispatching}';

    /**
     * The console command description.
     */
    protected $description = 'Generate scheduled export feeds that are due for regeneration';

    /**
     * Execute the console command.
     */
    public function handle(FeedSchedulerService $scheduler): int
    {
        $this->components->info('Checking for scheduled feeds...');

        if ($this->option('dry-run')) {
            return $this->handleDryRun($scheduler);
        }

        $count = $scheduler->processScheduledFeeds();

        if ($count > 0) {
            $this->components->info("Dispatched {$count} feed generation job(s).");
        } else {
            $this->components->info('No feeds due for generation.');
        }

        return self::SUCCESS;
    }

    /**
     * Show dry-run preview without dispatching jobs.
     */
    protected function handleDryRun(FeedSchedulerService $scheduler): int
    {
        $profiles = $scheduler->getProfilesDueForGeneration();

        if ($profiles->isEmpty()) {
            $this->components->info('No feeds due for generation.');
            return self::SUCCESS;
        }

        $rows = $profiles->map(fn($p) => [
            $p->id,
            mb_substr($p->name, 0, 40),
            $p->format,
            $p->schedule,
            $p->last_generated_at?->format('Y-m-d H:i') ?? 'never',
            $p->next_generation_at?->format('Y-m-d H:i') ?? 'null',
        ])->toArray();

        $this->table(
            ['ID', 'Name', 'Format', 'Schedule', 'Last Generated', 'Next At'],
            $rows
        );

        $this->components->warn("DRY RUN: No jobs were dispatched. {$profiles->count()} profile(s) would be processed.");

        return self::SUCCESS;
    }
}

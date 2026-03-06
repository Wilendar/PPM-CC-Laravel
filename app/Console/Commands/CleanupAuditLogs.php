<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class CleanupAuditLogs extends Command
{
    protected $signature = 'audit:cleanup {--days=90 : Days to keep} {--dry-run : Show count without deleting}';
    protected $description = 'Cleanup old audit log entries';

    public function handle(): int
    {
        $retentionService = app(\App\Services\RetentionConfigService::class);
        $days = $this->option('days')
            ? (int) $this->option('days')
            : $retentionService->getRetentionDays('audit_logs', 90);
        $dryRun = $this->option('dry-run');
        $cutoff = now()->subDays($days);

        $count = AuditLog::where('created_at', '<', $cutoff)->count();

        if ($dryRun) {
            $this->info("Dry run: {$count} audit logs older than {$days} days would be deleted.");
            return self::SUCCESS;
        }

        if ($count === 0) {
            $this->info('No audit logs to clean up.');
            return self::SUCCESS;
        }

        $deleted = 0;
        do {
            $batch = AuditLog::where('created_at', '<', $cutoff)->limit(1000)->delete();
            $deleted += $batch;
            $this->line("Deleted batch: {$batch} (total: {$deleted}/{$count})");
        } while ($batch > 0);

        $this->info("Cleanup complete: {$deleted} audit logs deleted (older than {$days} days).");

        return self::SUCCESS;
    }
}

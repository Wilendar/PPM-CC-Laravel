<?php

namespace App\Console\Commands;

use App\Models\CategoryPreview;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Cleanup Expired Category Previews Command
 *
 * ETAP_07 FAZA 3D: Category Import Preview System
 *
 * Purpose: Automatic cleanup dla expired category preview records
 * Schedule: Runs hourly via Laravel Scheduler
 * Business Logic: Remove expired previews + old approved/rejected records
 *
 * Cleanup Rules:
 * 1. Remove previews where expires_at < now()
 * 2. Remove previews with status = 'expired'
 * 3. Remove approved/rejected records older than 24h (audit retention)
 *
 * Performance: Simple delete query z expires_at index
 * Safety: Soft cleanup - nie affect active pending previews
 *
 * @package App\Console\Commands
 * @version 1.0
 * @since 2025-10-08
 */
class CleanupExpiredCategoryPreviews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'category-preview:cleanup
                            {--force : Force cleanup without confirmation}
                            {--dry-run : Show what would be deleted without deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup expired category preview records (auto-runs hourly)';

    /**
     * Execute the console command.
     *
     * Business Logic:
     * - Delete expired previews (expires_at < now)
     * - Delete old approved/rejected (created_at < 24h ago)
     * - Log cleanup statistics
     *
     * @return int Command exit code
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('🧹 Category Preview Cleanup Started');
        $this->info('Time: ' . now()->toDateTimeString());

        if ($isDryRun) {
            $this->warn('⚠️  DRY RUN MODE - No records will be deleted');
        }

        // STEP 1: Find expired previews
        $expiredQuery = CategoryPreview::where('expires_at', '<', now())
                                       ->orWhere('status', CategoryPreview::STATUS_EXPIRED);

        $expiredCount = $expiredQuery->count();

        if ($expiredCount > 0) {
            $this->line("📦 Found {$expiredCount} expired preview(s)");

            if ($isDryRun) {
                $expired = $expiredQuery->get();
                $this->table(
                    ['ID', 'Job ID', 'Shop ID', 'Status', 'Expires At'],
                    $expired->map(fn($p) => [
                        $p->id,
                        substr($p->job_id, 0, 8) . '...',
                        $p->shop_id,
                        $p->status,
                        $p->expires_at->diffForHumans()
                    ])
                );
            } else {
                $deleted = $expiredQuery->delete();
                $this->info("✅ Deleted {$deleted} expired preview(s)");

                Log::info('Category preview cleanup: expired records', [
                    'deleted_count' => $deleted,
                    'type' => 'expired'
                ]);
            }
        } else {
            $this->line('✨ No expired previews found');
        }

        // STEP 2: Find old approved/rejected previews (audit retention: 24h)
        $oldCompletedQuery = CategoryPreview::whereIn('status', [
                                                CategoryPreview::STATUS_APPROVED,
                                                CategoryPreview::STATUS_REJECTED
                                            ])
                                            ->where('created_at', '<', now()->subDay());

        $oldCompletedCount = $oldCompletedQuery->count();

        if ($oldCompletedCount > 0) {
            $this->line("📦 Found {$oldCompletedCount} old completed preview(s) (>24h)");

            if ($isDryRun) {
                $oldCompleted = $oldCompletedQuery->get();
                $this->table(
                    ['ID', 'Job ID', 'Status', 'Created At'],
                    $oldCompleted->map(fn($p) => [
                        $p->id,
                        substr($p->job_id, 0, 8) . '...',
                        $p->status,
                        $p->created_at->diffForHumans()
                    ])
                );
            } else {
                $deleted = $oldCompletedQuery->delete();
                $this->info("✅ Deleted {$deleted} old completed preview(s)");

                Log::info('Category preview cleanup: old completed records', [
                    'deleted_count' => $deleted,
                    'type' => 'old_completed'
                ]);
            }
        } else {
            $this->line('✨ No old completed previews found');
        }

        // STEP 3: Summary statistics
        $totalDeleted = $expiredCount + $oldCompletedCount;
        $remainingCount = CategoryPreview::count();

        $this->newLine();
        $this->info('📊 Cleanup Summary:');
        $this->line("   - Expired previews: {$expiredCount}");
        $this->line("   - Old completed: {$oldCompletedCount}");
        $this->line("   - Total cleaned: {$totalDeleted}");
        $this->line("   - Remaining active: {$remainingCount}");

        if ($isDryRun) {
            $this->newLine();
            $this->warn('⚠️  DRY RUN - No changes were made');
            $this->line('Run without --dry-run to perform actual cleanup');
        }

        $this->newLine();
        $this->info('✅ Cleanup completed successfully');

        return Command::SUCCESS;
    }
}

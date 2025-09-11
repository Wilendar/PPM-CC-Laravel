<?php

namespace App\Jobs;

use App\Models\BackupJob;
use App\Services\BackupService;
use App\Services\SettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ScheduledBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Job timeout (2 godziny)
     */
    public $timeout = 7200;

    /**
     * Maksymalne próby
     */
    public $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('scheduled-backups');
    }

    /**
     * Execute the job.
     */
    public function handle(BackupService $backupService, SettingsService $settingsService): void
    {
        try {
            Log::info("Starting scheduled backup job");

            // Sprawdź ustawienia automatycznych backupów
            $frequency = $settingsService->get('backup_frequency', 'daily');
            
            if ($frequency === 'manual') {
                Log::info("Automatic backups are disabled");
                return;
            }

            // Sprawdź czy już nie ma aktywnego backupu
            $activeBackup = BackupJob::active()->first();
            if ($activeBackup) {
                Log::info("Active backup already exists: {$activeBackup->id}");
                return;
            }

            // Sprawdź ostatni backup
            $lastBackup = BackupJob::completed()->latest('completed_at')->first();
            if ($lastBackup && !$this->shouldCreateNewBackup($lastBackup, $frequency)) {
                Log::info("Recent backup exists, skipping");
                return;
            }

            // Utwórz konfigurację backupu na podstawie ustawień
            $configuration = $this->getBackupConfiguration($settingsService);

            // Utwórz backup job
            $backup = $backupService->createBackup('database', $configuration);
            $backup->update([
                'name' => 'Scheduled_Backup_' . now()->format('Y-m-d_H-i-s')
            ]);

            Log::info("Created scheduled backup job: {$backup->id}");

            // Uruchom backup
            BackupDatabaseJob::dispatch($backup);

            // Zaplanuj następny backup
            $this->scheduleNext($frequency);

        } catch (\Exception $e) {
            Log::error("Scheduled backup job failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Sprawdź czy należy utworzyć nowy backup
     */
    private function shouldCreateNewBackup(?BackupJob $lastBackup, string $frequency): bool
    {
        if (!$lastBackup) {
            return true;
        }

        $cutoff = match($frequency) {
            'daily' => now()->subDay(),
            'weekly' => now()->subWeek(),
            'monthly' => now()->subMonth(),
            default => now()->subDay()
        };

        return $lastBackup->completed_at < $cutoff;
    }

    /**
     * Pobierz konfigurację backupu na podstawie ustawień
     */
    private function getBackupConfiguration(SettingsService $settingsService): array
    {
        $baseConfig = BackupJob::getDefaultConfiguration('database');

        return array_merge($baseConfig, [
            'compress' => $settingsService->get('backup_compress', true),
            'encrypt' => $settingsService->get('backup_encrypt', false),
            'retention_days' => $settingsService->get('backup_retention_days', 30),
            'include_logs' => $settingsService->get('backup_include_logs', false),
        ]);
    }

    /**
     * Zaplanuj następny automatyczny backup
     */
    private function scheduleNext(string $frequency): void
    {
        $nextRun = match($frequency) {
            'daily' => now()->addDay()->setTime(2, 0), // 02:00 następnego dnia
            'weekly' => now()->addWeek()->startOfWeek()->setTime(2, 0), // Poniedziałek 02:00
            'monthly' => now()->addMonth()->startOfMonth()->setTime(2, 0), // 1. dnia miesiąca 02:00
            default => now()->addDay()->setTime(2, 0)
        };

        // Planuj tylko jeśli nie ma już zaplanowanego
        $existingJob = \DB::table('jobs')
            ->where('payload', 'like', '%ScheduledBackupJob%')
            ->where('available_at', '>=', now()->timestamp)
            ->exists();

        if (!$existingJob) {
            static::dispatch()->delay($nextRun);
            Log::info("Scheduled next backup for: {$nextRun}");
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Scheduled backup job definitively failed", [
            'error' => $exception->getMessage()
        ]);

        // Spróbuj zaplanować następny backup mimo błędu
        try {
            $settingsService = app(SettingsService::class);
            $frequency = $settingsService->get('backup_frequency', 'daily');
            
            if ($frequency !== 'manual') {
                $this->scheduleNext($frequency);
            }
        } catch (\Exception $e) {
            Log::error("Failed to schedule next backup after failure", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'backup',
            'scheduled',
            'automatic'
        ];
    }

    /**
     * Retry the job with exponential backoff
     */
    public function backoff(): array
    {
        return [1800]; // 30 minut
    }

    /**
     * Determine if the job should be retried
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(2);
    }
}
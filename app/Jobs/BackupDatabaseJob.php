<?php

namespace App\Jobs;

use App\Models\BackupJob;
use App\Services\BackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BackupDatabaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Backup job model
     */
    protected $backupJob;

    /**
     * Job timeout (30 minut)
     */
    public $timeout = 1800;

    /**
     * Maksymalne próby
     */
    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(BackupJob $backupJob)
    {
        $this->backupJob = $backupJob;
        
        // Ustaw queue w zależności od typu backupu
        $this->onQueue($this->getQueueName());
    }

    /**
     * Execute the job.
     */
    public function handle(BackupService $backupService): void
    {
        try {
            Log::info("Starting backup job: {$this->backupJob->id} - {$this->backupJob->name}");

            // Wykonaj backup
            $success = $backupService->executeBackup($this->backupJob);

            if ($success) {
                Log::info("Backup job completed successfully: {$this->backupJob->id}");
                
                // Emit event dla Livewire
                event('backup.completed', [
                    'backup_id' => $this->backupJob->id,
                    'success' => true
                ]);
            } else {
                Log::error("Backup job failed: {$this->backupJob->id}");
                
                event('backup.completed', [
                    'backup_id' => $this->backupJob->id,
                    'success' => false
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Backup job exception: {$this->backupJob->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Oznacz jako nieudany
            $this->backupJob->markAsFailed($e->getMessage());

            // Re-throw aby Laravel mógł obsłużyć failed job
            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Backup job definitively failed: {$this->backupJob->id}", [
            'error' => $exception->getMessage()
        ]);

        // Oznacz jako nieudany jeśli nie zostało już oznaczone
        if ($this->backupJob->status !== BackupJob::STATUS_FAILED) {
            $this->backupJob->markAsFailed($exception->getMessage());
        }

        // Emit event
        event('backup.failed', [
            'backup_id' => $this->backupJob->id,
            'error' => $exception->getMessage()
        ]);
    }

    /**
     * Określ nazwę queue w zależności od typu backupu
     */
    private function getQueueName(): string
    {
        return match($this->backupJob->type) {
            BackupJob::TYPE_FULL => 'backups-heavy',
            BackupJob::TYPE_FILES => 'backups-medium', 
            BackupJob::TYPE_DATABASE => 'backups-light',
            default => 'backups-default'
        };
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'backup',
            'type:' . $this->backupJob->type,
            'backup_id:' . $this->backupJob->id
        ];
    }

    /**
     * Retry the job with exponential backoff
     */
    public function backoff(): array
    {
        return [60, 300, 900]; // 1min, 5min, 15min
    }

    /**
     * Determine if the job should be retried
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(6); // Próbuj przez 6 godzin
    }
}
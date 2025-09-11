<?php

namespace App\Jobs;

use App\Models\MaintenanceTask;
use App\Services\MaintenanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MaintenanceTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maintenance task model
     */
    protected $maintenanceTask;

    /**
     * Job timeout (zależny od typu zadania)
     */
    public $timeout;

    /**
     * Maksymalne próby
     */
    public $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(MaintenanceTask $maintenanceTask)
    {
        $this->maintenanceTask = $maintenanceTask;
        
        // Ustaw timeout w zależności od typu zadania
        $this->timeout = $this->getTimeoutForTaskType($maintenanceTask->type);
        
        // Ustaw queue w zależności od typu zadania
        $this->onQueue($this->getQueueName());
    }

    /**
     * Execute the job.
     */
    public function handle(MaintenanceService $maintenanceService): void
    {
        try {
            Log::info("Starting maintenance task: {$this->maintenanceTask->id} - {$this->maintenanceTask->name}");

            // Sprawdź czy zadanie wciąż może być wykonane
            $this->maintenanceTask->refresh();
            
            if (!$this->maintenanceTask->canRun()) {
                Log::warning("Maintenance task cannot run: {$this->maintenanceTask->id}");
                $this->maintenanceTask->update(['status' => MaintenanceTask::STATUS_SKIPPED]);
                return;
            }

            // Wykonaj zadanie
            $success = $maintenanceService->executeTask($this->maintenanceTask);

            if ($success) {
                Log::info("Maintenance task completed successfully: {$this->maintenanceTask->id}");
                
                // Emit event dla Livewire
                event('maintenance.completed', [
                    'task_id' => $this->maintenanceTask->id,
                    'success' => true
                ]);
            } else {
                Log::error("Maintenance task failed: {$this->maintenanceTask->id}");
                
                event('maintenance.completed', [
                    'task_id' => $this->maintenanceTask->id,
                    'success' => false
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Maintenance task exception: {$this->maintenanceTask->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Oznacz jako nieudany
            $this->maintenanceTask->markAsFailed($e->getMessage());

            // Re-throw aby Laravel mógł obsłużyć failed job
            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Maintenance task definitively failed: {$this->maintenanceTask->id}", [
            'error' => $exception->getMessage()
        ]);

        // Oznacz jako nieudany jeśli nie zostało już oznaczone
        if ($this->maintenanceTask->status !== MaintenanceTask::STATUS_FAILED) {
            $this->maintenanceTask->markAsFailed($exception->getMessage());
        }

        // Emit event
        event('maintenance.failed', [
            'task_id' => $this->maintenanceTask->id,
            'error' => $exception->getMessage()
        ]);
    }

    /**
     * Określ timeout w zależności od typu zadania
     */
    private function getTimeoutForTaskType(string $taskType): int
    {
        return match($taskType) {
            MaintenanceTask::TYPE_DB_OPTIMIZATION => 3600,    // 1 godzina
            MaintenanceTask::TYPE_INDEX_REBUILD => 7200,      // 2 godziny
            MaintenanceTask::TYPE_LOG_CLEANUP => 1800,        // 30 minut
            MaintenanceTask::TYPE_FILE_CLEANUP => 1800,       // 30 minut
            MaintenanceTask::TYPE_CACHE_CLEANUP => 300,       // 5 minut
            MaintenanceTask::TYPE_SECURITY_CHECK => 900,      // 15 minut
            MaintenanceTask::TYPE_STATS_UPDATE => 600,        // 10 minut
            default => 1800 // 30 minut default
        };
    }

    /**
     * Określ nazwę queue w zależności od typu zadania
     */
    private function getQueueName(): string
    {
        return match($this->maintenanceTask->type) {
            MaintenanceTask::TYPE_DB_OPTIMIZATION => 'maintenance-heavy',
            MaintenanceTask::TYPE_INDEX_REBUILD => 'maintenance-heavy',
            MaintenanceTask::TYPE_LOG_CLEANUP => 'maintenance-medium',
            MaintenanceTask::TYPE_FILE_CLEANUP => 'maintenance-medium',
            MaintenanceTask::TYPE_CACHE_CLEANUP => 'maintenance-light',
            MaintenanceTask::TYPE_SECURITY_CHECK => 'maintenance-light',
            MaintenanceTask::TYPE_STATS_UPDATE => 'maintenance-light',
            default => 'maintenance-default'
        };
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'maintenance',
            'type:' . $this->maintenanceTask->type,
            'task_id:' . $this->maintenanceTask->id,
            'recurring:' . ($this->maintenanceTask->is_recurring ? 'yes' : 'no')
        ];
    }

    /**
     * Retry the job with exponential backoff
     */
    public function backoff(): array
    {
        return [300, 900]; // 5min, 15min
    }

    /**
     * Determine if the job should be retried
     */
    public function retryUntil(): \DateTime
    {
        // Zadania krytyczne próbuj przez 4 godziny, inne przez 2 godziny
        $hours = in_array($this->maintenanceTask->type, [
            MaintenanceTask::TYPE_DB_OPTIMIZATION,
            MaintenanceTask::TYPE_SECURITY_CHECK
        ]) ? 4 : 2;
        
        return now()->addHours($hours);
    }

    /**
     * Determine if the job should fail on timeout
     */
    public function shouldFailOnTimeout(): bool
    {
        return true;
    }
}
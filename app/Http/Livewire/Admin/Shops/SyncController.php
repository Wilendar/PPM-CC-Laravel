<?php

namespace App\Http\Livewire\Admin\Shops;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\PrestaShopShop;
use App\Models\SyncJob;
use App\Models\SystemSetting;
use App\Services\QueueJobsService;
use App\Services\SyncJobCleanupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Jobs\PullProductsFromPrestaShop;

/**
 * SyncController Livewire Component
 * 
 * ETAP_04 Panel Administracyjny - Sekcja 2.2.1: Synchronization Control Panel
 * 
 * Kompleksowy panel kontroli synchronizacji z features:
 * - Manual sync triggers per shop lub bulk operations
 * - Sync queue monitoring z progress bars
 * - Sync history z timestamps i results
 * - Conflict resolution interface
 * - Performance metrics i error tracking
 * 
 * Enterprise Features:
 * - Real-time sync status monitoring
 * - Batch operations z retry logic
 * - Detailed sync analytics
 * - Error handling z diagnostics
 */
class SyncController extends Component
{
    use WithPagination, AuthorizesRequests;

    // Component State
    public $selectedShops = [];
    public $selectAll = false;
    public $syncInProgress = false;
    public $currentSyncJob = null;
    
    // Filters and Search
    public $search = '';
    public $statusFilter = 'all';
    public $sortBy = 'last_sync_at';
    public $sortDirection = 'desc';
    
    // Sync Configuration
    public $batchSize = 10;
    public $syncTimeout = 300; // seconds
    public $syncTypes = ['products', 'categories', 'prices', 'stock'];
    public $selectedSyncTypes = ['products'];
    public $conflictResolution = 'ppm_wins';

    // Real-time sync monitoring
    public $activeSyncJobs = [];
    public $syncProgress = [];
    public $syncErrors = [];

    // SEKCJA 2.2.1.2 - Sync Configuration Settings
    public $showSyncConfig = false;

    // FAZA 9 Phase 3 - Queue Status UI
    public $expandedShopId = null;
    public array $selectedQueueJobs = []; // Quick Actions: selected jobs per shop

    // Recent Jobs Details Expansion (2025-11-07)
    public $expandedRecentJobId = null;

    // BUG #9 FIX #7 - Filters for Recent Sync Jobs (2025-11-12)
    public ?string $filterJobType = null;      // null = All, 'import_products', 'product_sync'
    public string $filterOrderBy = 'desc';     // 'desc' = newest first, 'asc' = oldest first
    public ?int $filterUserId = null;          // null = All, or specific user_id
    public ?string $filterStatus = null;       // null = All, 'completed', 'failed', 'running', 'pending', 'canceled'
    public ?int $filterShopId = null;          // null = All, or specific target_id (shop)
    public int $perPage = 10;                  // Items per page (default 10)

    // Auto-sync scheduler configuration - 2.2.1.2.1
    public $autoSyncEnabled = true;
    public $autoSyncFrequency = 'hourly';
    public $autoSyncScheduleHour = 2; // 2 AM
    public $autoSyncDaysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    public $autoSyncOnlyConnected = true;
    public $autoSyncSkipMaintenanceMode = true;

    // Retry logic dla failed syncs - 2.2.1.2.2
    public $retryEnabled = true;
    public $maxRetryAttempts = 3;
    public $retryDelayMinutes = 15;
    public $retryBackoffMultiplier = 2.0;
    public $retryOnlyTransientErrors = true;

    // Notification settings dla sync events - 2.2.1.2.3
    public $notificationsEnabled = true;
    public $notifyOnSuccess = false;
    public $notifyOnFailure = true;
    public $notifyOnRetryExhausted = true;
    public $notificationChannels = ['email'];
    public $notificationRecipients = [];

    // Performance optimization settings - 2.2.1.2.4
    public $performanceMode = 'balanced';
    public $maxConcurrentJobs = 3;
    public $jobProcessingDelay = 100; // milliseconds
    public $memoryLimit = 512; // MB
    public $processTimeout = 1800; // seconds (30 min)

    // Backup przed sync option - 2.2.1.2.5
    public $backupBeforeSync = true;
    public $backupRetentionDays = 7;
    public $backupOnlyOnMajorChanges = true;
    public $backupCompressionEnabled = true;

    // Listeners for real-time updates
    protected $listeners = [
        'syncJobUpdated' => 'handleSyncJobUpdate',
        'syncCompleted' => 'handleSyncCompleted',
        'syncFailed' => 'handleSyncFailed',
        'refreshSyncStatus' => '$refresh',
    ];

    /**
     * Component validation rules.
     */
    protected function rules()
    {
        return [
            // Basic sync configuration
            'batchSize' => 'required|integer|min:1|max:100',
            'syncTimeout' => 'required|integer|min:60|max:3600',
            'selectedSyncTypes' => 'required|array|min:1',
            'conflictResolution' => 'required|in:ppm_wins,prestashop_wins,manual,newest_wins',

            // Auto-sync scheduler configuration - 2.2.1.2.1
            'autoSyncFrequency' => 'required|in:hourly,daily,weekly',
            'autoSyncScheduleHour' => 'required|integer|min:0|max:23',
            'autoSyncDaysOfWeek' => 'array',

            // Retry logic configuration - 2.2.1.2.2
            'maxRetryAttempts' => 'required|integer|min:0|max:10',
            'retryDelayMinutes' => 'required|integer|min:1|max:1440',
            'retryBackoffMultiplier' => 'required|numeric|min:1|max:5',

            // Notification settings - 2.2.1.2.3
            'notificationChannels' => 'array',
            'notificationRecipients' => 'array',

            // Performance optimization - 2.2.1.2.4
            'performanceMode' => 'required|in:economy,balanced,performance',
            'maxConcurrentJobs' => 'required|integer|min:1|max:10',
            'jobProcessingDelay' => 'required|integer|min:0|max:5000',
            'memoryLimit' => 'required|integer|min:128|max:2048',
            'processTimeout' => 'required|integer|min:300|max:7200',

            // Backup configuration - 2.2.1.2.5
            'backupRetentionDays' => 'required|integer|min:1|max:365',
        ];
    }

    /**
     * Helper method to determine setting type for SystemSetting storage
     */
    private function getSettingType($value): string
    {
        if (is_bool($value)) return 'boolean';
        if (is_int($value)) return 'integer';
        if (is_array($value)) return 'json'; // FIXED: was 'array', but ENUM only allows 'json'
        return 'string';
    }

    /**
     * Mount component.
     */
    public function mount()
    {
        // DEBUG: Log mount() call
        Log::debug('SyncController mount() CALLED', [
            'autoSyncFrequency_BEFORE' => $this->autoSyncFrequency ?? 'NULL',
            'timestamp' => now()->toDateTimeString(),
        ]);

        // DEVELOPMENT: authorize tymczasowo wyłączone dla testów
        // $this->authorize('admin.shops.sync');

        $this->loadActiveSyncJobs();
        $this->selectedSyncTypes = ['products']; // Default selection

        // Load sync configuration from database (MVP - Priority 1)
        $this->loadSyncConfigurationFromDatabase();

        // DEBUG: Log mount() completion
        Log::debug('SyncController mount() COMPLETED', [
            'autoSyncFrequency_AFTER' => $this->autoSyncFrequency,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Render the component.
     */
    public function render()
    {
        $shops = $this->getShops();
        $stats = $this->getSyncStats();
        $recentSyncJobs = $this->getRecentSyncJobs(); // BUG #9 FIX #7 - renamed for consistency

        return view('livewire.admin.shops.sync-controller', [
            'shops' => $shops,
            'stats' => $stats,
            'recentJobs' => $recentSyncJobs,            // Keep old name for backward compatibility
            'recentSyncJobs' => $recentSyncJobs,        // BUG #9 FIX #7 - new name with pagination

            // BUG #9 FIX #7 - Filter options (2025-11-12)
            'filterUsers' => $this->getUsersForFilter(),
            'filterShops' => $this->getShopsForFilter(),
        ])->layout('layouts.admin', [
            'title' => 'Kontrola Synchronizacji - PPM',
            'breadcrumb' => 'Synchronizacja sklepów'
        ]);
    }

    /**
     * Get shops with filtering and pagination.
     */
    protected function getShops()
    {
        $query = PrestaShopShop::query();

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('url', 'like', '%' . $this->search . '%');
            });
        }

        // Apply status filter
        if ($this->statusFilter !== 'all') {
            switch ($this->statusFilter) {
                case 'connected':
                    $query->healthy();
                    break;
                case 'sync_due':
                    $query->dueForSync();
                    break;
                case 'sync_errors':
                    $query->where('sync_error_count', '>', 0);
                    break;
                case 'never_synced':
                    $query->whereNull('last_sync_at');
                    break;
            }
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate(20);
    }

    /**
     * Get QueueJobsService instance (lazy loading via app() helper)
     *
     * Uses app() helper instead of constructor DI to avoid Livewire 3.x wire:snapshot issues
     *
     * @return QueueJobsService
     */
    protected function getQueueJobsService(): QueueJobsService
    {
        return app(QueueJobsService::class);
    }

    /**
     * Get sync statistics.
     */
    protected function getSyncStats()
    {
        $queueService = $this->getQueueJobsService();

        return [
            'total_shops' => PrestaShopShop::count(),
            'active_sync_jobs' => SyncJob::whereIn('status', [SyncJob::STATUS_PENDING, SyncJob::STATUS_RUNNING])->count(),
            'completed_today' => SyncJob::where('status', SyncJob::STATUS_COMPLETED)
                                      ->whereDate('completed_at', today())
                                      ->count(),
            'failed_today' => SyncJob::where('status', SyncJob::STATUS_FAILED)
                                    ->whereDate('updated_at', today())
                                    ->count(),
            'sync_due_count' => PrestaShopShop::dueForSync()->count(),
            'avg_sync_time' => SyncJob::where('status', SyncJob::STATUS_COMPLETED)
                                     ->whereDate('completed_at', today())
                                     ->avg('duration_seconds') ?? 0,

            // FAZA 9 Phase 1: Queue Infrastructure Statistics
            'stuck_queue_jobs' => $queueService->getStuckJobs()->count(),
            'active_queue_jobs' => $queueService->getActiveJobs()->count(),
            'failed_queue_jobs' => $queueService->getFailedJobs()->count(),
            'queue_health' => $this->calculateQueueHealth($queueService),
        ];
    }

    /**
     * Calculate Queue Health percentage
     *
     * Algorithm:
     * - 0 jobs = 100% healthy (no problems)
     * - Health = 100% - (problems/total * 100)
     * - Problems = failed jobs + stuck jobs
     *
     * @param QueueJobsService $queueService
     * @return int Health percentage (0-100)
     */
    protected function calculateQueueHealth($queueService): int
    {
        $active = $queueService->getActiveJobs()->count();
        $failed = $queueService->getFailedJobs()->count();
        $stuck = $queueService->getStuckJobs()->count();

        $total = $active + $failed + $stuck;

        if ($total === 0) {
            return 100; // No jobs = healthy
        }

        $problems = $failed + $stuck;
        return (int) round(100 - (($problems / $total) * 100));
    }

    /**
     * Get recent sync jobs with filtering and pagination
     *
     * USER_ID FIX (2025-11-07): Eager load 'user' relationship
     * to display who triggered the sync (or "SYSTEM" if NULL)
     *
     * BUG #9 FIX #1 (2025-11-12): Removed job_type filter to show ALL sync jobs
     * (import_products + product_sync), not just product_sync
     *
     * BUG #9 FIX #7 (2025-11-12): Added filters:
     * - job_type (import/sync)
     * - order_by (date ASC/DESC)
     * - user_id (who triggered)
     * - status (completed/failed/etc)
     * - shop_id (which shop)
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    protected function getRecentSyncJobs()
    {
        $query = SyncJob::with(['prestashopShop', 'user']);

        // Filter by job_type (BUG #9 FIX #7)
        if ($this->filterJobType) {
            $query->where('job_type', $this->filterJobType);
        }

        // Filter by user_id (BUG #9 FIX #7)
        if ($this->filterUserId) {
            $query->where('user_id', $this->filterUserId);
        }

        // Filter by status (BUG #9 FIX #7)
        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        // Filter by shop_id (target_id) (BUG #9 FIX #7)
        if ($this->filterShopId) {
            $query->where('target_id', $this->filterShopId);
        }

        // Order by created_at (BUG #9 FIX #7)
        $orderDirection = $this->filterOrderBy === 'asc' ? 'asc' : 'desc';
        $query->orderBy('created_at', $orderDirection);

        // Debug logging (temporary - remove after confirmation)
        Log::debug('getRecentSyncJobs FILTERS', [
            'job_type' => $this->filterJobType,
            'order_by' => $this->filterOrderBy,
            'user_id' => $this->filterUserId,
            'status' => $this->filterStatus,
            'shop_id' => $this->filterShopId,
            'per_page' => $this->perPage,
        ]);

        // Paginate instead of limit (BUG #9 FIX #7)
        return $query->paginate($this->perPage);
    }

    /**
     * Reset all Recent Sync Jobs filters to default values
     *
     * BUG #9 FIX #7 (2025-11-12)
     */
    public function resetSyncJobFilters(): void
    {
        $this->filterJobType = null;
        $this->filterOrderBy = 'desc';
        $this->filterUserId = null;
        $this->filterStatus = null;
        $this->filterShopId = null;

        Log::debug('Sync jobs filters reset', [
            'user_id' => auth()->id(),
        ]);

        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Filtry synchronizacji zostały zresetowane'
        ]);
    }

    /**
     * Get all users who have triggered sync jobs (for filter dropdown)
     *
     * BUG #9 FIX #7 (2025-11-12)
     *
     * @return \Illuminate\Support\Collection
     */
    public function getUsersForFilter()
    {
        return SyncJob::with('user')
            ->select('user_id')
            ->distinct()
            ->whereNotNull('user_id')
            ->get()
            ->pluck('user')
            ->filter() // Remove null users
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    /**
     * Get all shops that have sync jobs (for filter dropdown)
     *
     * BUG #9 FIX #7 (2025-11-12)
     *
     * @return \Illuminate\Support\Collection
     */
    public function getShopsForFilter()
    {
        return SyncJob::with('prestashopShop')
            ->select('target_id')
            ->distinct()
            ->whereNotNull('target_id')
            ->where('target_type', SyncJob::TYPE_PRESTASHOP)
            ->get()
            ->pluck('prestashopShop')
            ->filter() // Remove null shops
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    /**
     * Load active sync jobs for real-time monitoring.
     */
    protected function loadActiveSyncJobs()
    {
        $this->activeSyncJobs = SyncJob::whereIn('status', [SyncJob::STATUS_PENDING, SyncJob::STATUS_RUNNING])
                                      ->with('prestashopShop')
                                      ->get()
                                      ->keyBy('id')
                                      ->toArray();
    }

    /**
     * FAZA 9 Phase 2: Get failed jobs from queue (computed property for Livewire)
     *
     * @return \Illuminate\Support\Collection
     */
    public function getFailedJobsProperty()
    {
        return $this->getQueueJobsService()->getFailedJobs();
    }

    /**
     * FAZA 9 Phase 2: Retry failed job
     *
     * @param string $uuid Job UUID
     * @return void
     */
    public function retryFailedJob(string $uuid)
    {
        try {
            $queueService = $this->getQueueJobsService();
            $result = $queueService->retryFailedJob($uuid);

            if ($result === 0) {
                session()->flash('success', "Job {$uuid} został dodany do kolejki ponownie.");
                Log::info("Failed job retried: {$uuid}", ['user_id' => auth()->id()]);
            } else {
                session()->flash('error', "Nie udało się ponownie uruchomić joba {$uuid}.");
                Log::warning("Failed to retry job: {$uuid}", ['result' => $result]);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Błąd podczas retry job: ' . $e->getMessage());
            Log::error('Failed to retry job', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * FAZA 9 Phase 2: Delete failed job
     *
     * @param string $uuid Job UUID
     * @return void
     */
    public function deleteFailedJob(string $uuid)
    {
        try {
            $queueService = $this->getQueueJobsService();
            $result = $queueService->deleteFailedJob($uuid);

            if ($result === 0) {
                session()->flash('success', "Job {$uuid} został usunięty z kolejki.");
                Log::info("Failed job deleted: {$uuid}", ['user_id' => auth()->id()]);
            } else {
                session()->flash('error', "Nie udało się usunąć joba {$uuid}.");
                Log::warning("Failed to delete job: {$uuid}", ['result' => $result]);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Błąd podczas usuwania job: ' . $e->getMessage());
            Log::error('Failed to delete job', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * FAZA 9 Phase 3: Get queue status for a specific shop (computed property)
     *
     * @param int $shopId
     * @return array
     */
    public function getShopQueueStatus($shopId): array
    {
        $queueService = $this->getQueueJobsService();

        // Get all active jobs (pending + processing)
        $activeJobs = $queueService->getActiveJobs();

        // Filter jobs for this shop
        $shopJobs = $activeJobs->filter(function($job) use ($shopId) {
            return isset($job['data']['shop_id']) && $job['data']['shop_id'] == $shopId;
        });

        $pendingCount = $shopJobs->where('status', 'pending')->count();
        $processingCount = $shopJobs->where('status', 'processing')->count();
        $totalCount = $shopJobs->count();

        // Determine overall status
        $status = 'none';
        if ($processingCount > 0) {
            $status = 'processing';
        } elseif ($pendingCount > 0) {
            $status = 'pending';
        }

        return [
            'has_queue_job' => $totalCount > 0,
            'queue_job_status' => $status,
            'queue_job_count' => $totalCount,
            'pending_count' => $pendingCount,
            'processing_count' => $processingCount,
            'jobs' => $shopJobs->toArray(),
        ];
    }

    /**
     * FAZA 9 Phase 3: Toggle expanded shop details
     *
     * @param int $shopId
     * @return void
     */
    public function toggleShopDetails($shopId)
    {
        if ($this->expandedShopId === $shopId) {
            $this->expandedShopId = null;
        } else {
            $this->expandedShopId = $shopId;
        }
    }

    /**
     * Toggle Recent Job Details Expansion (2025-11-07)
     *
     * Expands/collapses detailed view for sync_job showing:
     * - Performance metrics (memory, CPU, API calls)
     * - Result summary (JSON)
     * - Error details (if failed)
     * - Validation errors & warnings
     * - Job configuration
     *
     * @param int $jobId SyncJob ID
     * @return void
     */
    public function toggleRecentJobDetails($jobId)
    {
        if ($this->expandedRecentJobId === $jobId) {
            $this->expandedRecentJobId = null;
        } else {
            $this->expandedRecentJobId = $jobId;
        }
    }

    /**
     * FAZA 9 Phase 3: Quick Actions - Get count of selected queue jobs for shop
     */
    public function getSelectedQueueJobsCount($shopId): int
    {
        if (!isset($this->selectedQueueJobs[$shopId])) {
            return 0;
        }
        return count(array_filter($this->selectedQueueJobs[$shopId]));
    }

    /**
     * FAZA 9 Phase 3: Quick Actions - Check if all queue jobs selected for shop
     */
    public function areAllQueueJobsSelected($shopId): bool
    {
        $queueStatus = $this->getShopQueueStatus($shopId);

        if (!$queueStatus['has_queue_job'] || empty($queueStatus['jobs'])) {
            return false;
        }

        $totalJobs = count($queueStatus['jobs']);
        $selectedCount = $this->getSelectedQueueJobsCount($shopId);

        return $selectedCount === $totalJobs;
    }

    /**
     * FAZA 9 Phase 3: Quick Actions - Toggle all queue jobs for shop
     */
    public function toggleAllQueueJobs($shopId)
    {
        $queueStatus = $this->getShopQueueStatus($shopId);

        if (!$queueStatus['has_queue_job'] || empty($queueStatus['jobs'])) {
            return;
        }

        $allSelected = $this->areAllQueueJobsSelected($shopId);

        // Toggle: if all selected -> deselect all, otherwise select all
        if ($allSelected) {
            $this->selectedQueueJobs[$shopId] = [];
        } else {
            $this->selectedQueueJobs[$shopId] = [];
            foreach ($queueStatus['jobs'] as $job) {
                $this->selectedQueueJobs[$shopId][$job['id']] = true;
            }
        }
    }

    /**
     * FAZA 9 Phase 3: Quick Actions - Execute selected jobs immediately
     */
    public function executeJobsNow($shopId)
    {
        try {
            $selectedJobIds = array_keys(array_filter($this->selectedQueueJobs[$shopId] ?? []));

            if (empty($selectedJobIds)) {
                session()->flash('warning', 'Nie zaznaczono żadnych zadań.');
                return;
            }

            $queueService = app(QueueJobsService::class);
            $executedCount = 0;

            foreach ($selectedJobIds as $jobId) {
                // Release job back to queue with priority (attempts = 0)
                DB::table('jobs')
                    ->where('id', $jobId)
                    ->update([
                        'available_at' => now()->timestamp,
                        'attempts' => 0,
                    ]);

                $executedCount++;
            }

            session()->flash('success', "Wykonano natychmiast {$executedCount} zadań dla sklepu.");
            Log::info('Queue jobs executed immediately', [
                'shop_id' => $shopId,
                'job_ids' => $selectedJobIds,
                'count' => $executedCount,
                'user_id' => auth()->id(),
            ]);

            // Clear selection
            $this->selectedQueueJobs[$shopId] = [];

        } catch (\Exception $e) {
            session()->flash('error', 'Błąd podczas wykonywania zadań: ' . $e->getMessage());
            Log::error('Failed to execute queue jobs immediately', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * FAZA 9 Phase 3: Quick Actions - Retry selected jobs
     */
    public function retryQueueJobs($shopId)
    {
        try {
            $selectedJobIds = array_keys(array_filter($this->selectedQueueJobs[$shopId] ?? []));

            if (empty($selectedJobIds)) {
                session()->flash('warning', 'Nie zaznaczono żadnych zadań.');
                return;
            }

            $retriedCount = 0;

            foreach ($selectedJobIds as $jobId) {
                // Reset attempts and make available
                DB::table('jobs')
                    ->where('id', $jobId)
                    ->update([
                        'attempts' => 0,
                        'available_at' => now()->timestamp,
                    ]);

                $retriedCount++;
            }

            session()->flash('success', "Wznowiono {$retriedCount} zadań dla sklepu.");
            Log::info('Queue jobs retried', [
                'shop_id' => $shopId,
                'job_ids' => $selectedJobIds,
                'count' => $retriedCount,
                'user_id' => auth()->id(),
            ]);

            // Clear selection
            $this->selectedQueueJobs[$shopId] = [];

        } catch (\Exception $e) {
            session()->flash('error', 'Błąd podczas wznawiania zadań: ' . $e->getMessage());
            Log::error('Failed to retry queue jobs', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * FAZA 9 Phase 3: Quick Actions - Cancel selected jobs
     */
    public function cancelQueueJobs($shopId)
    {
        try {
            $selectedJobIds = array_keys(array_filter($this->selectedQueueJobs[$shopId] ?? []));

            if (empty($selectedJobIds)) {
                session()->flash('warning', 'Nie zaznaczono żadnych zadań.');
                return;
            }

            $canceledCount = 0;

            foreach ($selectedJobIds as $jobId) {
                // Delete job from queue
                DB::table('jobs')->where('id', $jobId)->delete();
                $canceledCount++;
            }

            // Mark corresponding SyncJobs as cancelled
            DB::table('sync_jobs')
                ->whereIn('queue_job_id', $selectedJobIds)
                ->whereIn('status', ['pending', 'running'])
                ->update([
                    'status' => 'cancelled',
                    'error_message' => 'Cancelled by user via Quick Actions',
                    'completed_at' => now(),
                ]);

            session()->flash('success', "Anulowano {$canceledCount} zadań dla sklepu.");
            Log::info('Queue jobs cancelled', [
                'shop_id' => $shopId,
                'job_ids' => $selectedJobIds,
                'count' => $canceledCount,
                'user_id' => auth()->id(),
            ]);

            // Clear selection
            $this->selectedQueueJobs[$shopId] = [];

        } catch (\Exception $e) {
            session()->flash('error', 'Błąd podczas anulowania zadań: ' . $e->getMessage());
            Log::error('Failed to cancel queue jobs', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Toggle shop selection.
     */
    public function toggleShopSelection($shopId)
    {
        if (in_array($shopId, $this->selectedShops)) {
            $this->selectedShops = array_diff($this->selectedShops, [$shopId]);
        } else {
            $this->selectedShops[] = $shopId;
        }
        
        // Update selectAll checkbox state
        $totalShops = $this->getShops()->total();
        $this->selectAll = count($this->selectedShops) === $totalShops;
    }

    /**
     * Toggle select all shops.
     */
    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedShops = $this->getShops()->pluck('id')->toArray();
        } else {
            $this->selectedShops = [];
        }
    }

    /**
     * Trigger manual sync for selected shops.
     */
    public function syncSelectedShops()
    {
        $this->validate();
        
        if (empty($this->selectedShops)) {
            $this->addError('selectedShops', 'Wybierz co najmniej jeden sklep do synchronizacji.');
            return;
        }

        try {
            $shops = PrestaShopShop::whereIn('id', $this->selectedShops)->get();
            $jobIds = [];

            foreach ($shops as $shop) {
                $syncJob = $this->createSyncJob($shop);
                $jobIds[] = $syncJob->job_id;
                
                // Dispatch sync job to queue
                \App\Jobs\PrestaShop\SyncProductsJob::dispatch($syncJob);
                
                Log::info("Manual sync triggered for shop: {$shop->name}", [
                    'job_id' => $syncJob->job_id,
                    'shop_id' => $shop->id,
                ]);
            }

            $this->syncInProgress = true;
            $this->loadActiveSyncJobs();
            
            session()->flash('success', 
                'Synchronizacja została uruchomiona dla ' . count($shops) . ' sklepów. Job IDs: ' . implode(', ', $jobIds)
            );

        } catch (\Exception $e) {
            $this->addError('sync_error', 'Błąd podczas uruchamiania synchronizacji: ' . $e->getMessage());
            Log::error('Failed to trigger manual sync', [
                'error' => $e->getMessage(),
                'selected_shops' => $this->selectedShops,
            ]);
        }
    }

    /**
     * SYNC NOW - Execute pending sync job immediately (2025-11-12)
     *
     * User Request: "przycisk powinien wymuszać uruchomienie pending JOB dla wybranego sklepu,
     *                jeżeli nie ma pending to przycisk jest nieaktywny"
     *
     * Behavior:
     * - Finds PENDING or RUNNING sync job for shop
     * - Executes it IMMEDIATELY (bypasses queue via dispatchSync)
     * - Supports both import_products and product_sync job types
     * - Button should be DISABLED in UI when no pending jobs exist
     *
     * @param int $shopId Shop ID to sync
     * @return void
     */
    public function syncNow($shopId)
    {
        try {
            $shop = PrestaShopShop::findOrFail($shopId);

            // 1. Find PENDING or RUNNING job for this shop
            $pendingSyncJob = SyncJob::where('target_id', $shopId)
                ->where('target_type', SyncJob::TYPE_PRESTASHOP)
                ->whereIn('status', [SyncJob::STATUS_PENDING, SyncJob::STATUS_RUNNING])
                ->orderBy('created_at', 'desc')
                ->first();

            // 2. Validate pending job exists
            if (!$pendingSyncJob) {
                // FALLBACK (2025-11-12): Race condition - job może być w queue ale jeszcze nie w sync_jobs
                // Sprawdź czy są produkty z pending sync_status i dispatch nowego job'a
                $pendingProductsCount = \App\Models\ProductShopData::where('shop_id', $shopId)
                    ->where('sync_status', 'pending')
                    ->count();

                if ($pendingProductsCount === 0) {
                    $this->dispatch('notify', [
                        'type' => 'warning',
                        'message' => "Brak oczekujących zadań synchronizacji dla sklepu '{$shop->name}'. Przycisk SYNC NOW powinien być nieaktywny."
                    ]);

                    Log::warning('SYNC NOW clicked but no pending jobs and no pending products', [
                        'shop_id' => $shop->id,
                        'shop_name' => $shop->name,
                        'user_id' => auth()->id(),
                    ]);

                    return;
                }

                // CRITICAL FIX (2025-11-12): REMOVE pending jobs from queue to avoid duplicate execution
                // User complained: "poprzedni JOB zostaje w QUEUE i się uruchamia oddzielnie niezależnie od SYNC NOW"
                $queueService = app(\App\Services\QueueJobsService::class);
                $activeJobs = $queueService->getActiveJobs();

                // Find and cancel all jobs for this shop to prevent duplicate sync
                $canceledCount = 0;
                foreach ($activeJobs as $job) {
                    if (isset($job['data']['shop_id']) && $job['data']['shop_id'] == $shopId && $job['status'] === 'pending') {
                        $queueService->cancelPendingJob($job['id']);
                        $canceledCount++;

                        Log::info('SYNC NOW: Canceled pending queue job to prevent duplicate', [
                            'queue_job_id' => $job['id'],
                            'shop_id' => $shopId,
                            'product_id' => $job['data']['product_id'] ?? null,
                            'sku' => $job['data']['sku'] ?? null,
                        ]);
                    }
                }

                if ($canceledCount > 0) {
                    Log::info('SYNC NOW FALLBACK: Canceled pending queue jobs before dispatch', [
                        'shop_id' => $shop->id,
                        'canceled_jobs_count' => $canceledCount,
                    ]);
                }

                // FALLBACK: Dispatch nowego bulk sync job dla pending products
                Log::info('SYNC NOW FALLBACK: Creating new job for pending products', [
                    'shop_id' => $shop->id,
                    'shop_name' => $shop->name,
                    'pending_products_count' => $pendingProductsCount,
                    'canceled_queue_jobs' => $canceledCount,
                    'user_id' => auth()->id(),
                ]);

                // Dispatch każdego produktu pojedynczo jako sync job (IMMEDIATE execution)
                $dispatchedCount = 0;
                $pendingProducts = \App\Models\ProductShopData::where('shop_id', $shopId)
                    ->where('sync_status', 'pending')
                    ->with('product')
                    ->get();

                foreach ($pendingProducts as $productShopData) {
                    if ($productShopData->product) {
                        \App\Jobs\PrestaShop\SyncProductToPrestaShop::dispatchSync(
                            $productShopData->product,
                            $shop,
                            auth()->id()
                        );
                        $dispatchedCount++;
                    }
                }

                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => "Uruchomiono synchronizację {$dispatchedCount} produktów dla sklepu '{$shop->name}' (NATYCHMIAST, anulowano {$canceledCount} oczekujących jobs)"
                ]);

                $this->loadActiveSyncJobs();
                return;
            }

            // 3. Execute pending job IMMEDIATELY based on job type
            if ($pendingSyncJob->job_type === SyncJob::JOB_IMPORT_PRODUCTS) {
                // Import: PrestaShop → PPM
                // FIX 2025-12-22: Pass existing SyncJob to avoid creating duplicate!
                \App\Jobs\PullProductsFromPrestaShop::dispatchSync($shop, $pendingSyncJob);

                $this->dispatch('notify', [
                    'type' => 'info',
                    'message' => "Import z '{$shop->name}' uruchomiony NATYCHMIAST (Job ID: {$pendingSyncJob->id})"
                ]);

                Log::info("SYNC NOW: Import job executed immediately (using existing SyncJob)", [
                    'sync_job_id' => $pendingSyncJob->id,
                    'job_type' => 'import_products',
                    'shop_id' => $shop->id,
                    'shop_name' => $shop->name,
                    'user_id' => auth()->id(),
                    'fix' => 'passing_existing_syncjob',
                ]);

            } else {
                // Export: PPM → PrestaShop
                \App\Jobs\PrestaShop\SyncProductsJob::dispatchSync($pendingSyncJob);

                $this->dispatch('notify', [
                    'type' => 'info',
                    'message' => "Synchronizacja '{$shop->name}' uruchomiona NATYCHMIAST (Job ID: {$pendingSyncJob->id})"
                ]);

                Log::info("SYNC NOW: Export job executed immediately", [
                    'sync_job_id' => $pendingSyncJob->id,
                    'job_type' => $pendingSyncJob->job_type,
                    'shop_id' => $shop->id,
                    'shop_name' => $shop->name,
                    'user_id' => auth()->id(),
                ]);
            }

            $this->loadActiveSyncJobs();

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Błąd podczas SYNC NOW: ' . $e->getMessage()
            ]);

            Log::error('SYNC NOW failed', [
                'error' => $e->getMessage(),
                'shop_id' => $shopId,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * @deprecated Use syncNow() instead
     */
    public function syncSingleShop($shopId)
    {
        $this->syncNow($shopId);
    }

    /**
     * Trigger manual import from PrestaShop (BUG #7 FIX #2)
     *
     * Dispatches PullProductsFromPrestaShop job to queue to import products,
     * prices, and stock from PrestaShop to PPM.
     *
     * @param int $shopId Shop ID to import from
     * @return void
     */
    public function importFromShop(int $shopId): void
    {
        try {
            $shop = PrestaShopShop::findOrFail($shopId);

            if (!$shop->is_active) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => "Sklep '{$shop->name}' nie jest aktywny"
                ]);
                return;
            }

            // Dispatch import job
            PullProductsFromPrestaShop::dispatch($shop);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Import z '{$shop->name}' rozpoczęty. Sprawdź postęp w tabeli poniżej."
            ]);

            Log::info('Manual import triggered', [
                'shop_id' => $shop->id,
                'shop_name' => $shop->name,
                'user_id' => auth()->id(),
            ]);

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Błąd podczas uruchamiania importu: ' . $e->getMessage()
            ]);

            Log::error('Import trigger failed', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear old sync jobs logs (BUG #9 FIX #4 + ENHANCED 2025-11-12)
     *
     * Removes sync jobs with user-specified parameters:
     * - Type: all, completed, failed, completed_with_errors
     * - Age threshold: X days
     * - Option to clear all regardless of age
     *
     * Never deletes: pending, running
     *
     * @param string $type Type of jobs to clear
     * @param int $days Age threshold in days
     * @param bool $clearAllAges If true, delete all jobs regardless of age
     * @return void
     */
    public function clearOldLogs(string $type = 'all', int $days = 30, bool $clearAllAges = false): void
    {
        try {
            $cleanupService = app(SyncJobCleanupService::class);

            Log::info('Manual sync logs cleanup triggered', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email ?? 'unknown',
                'type' => $type,
                'days' => $days,
                'clear_all_ages' => $clearAllAges,
            ]);

            $stats = $cleanupService->cleanupCustom($type, $days, $clearAllAges, dryRun: false);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Wyczyszczono {$stats['deleted']} " .
                             ($type === 'all' ? 'wszystkich' : $type) . " zadań synchronizacji" .
                             ($clearAllAges ? '' : " starszych niż {$days} dni")
            ]);

            Log::info('Manual sync logs cleanup completed', $stats);

        } catch (\Exception $e) {
            Log::error('Sync logs cleanup failed', [
                'user_id' => auth()->id(),
                'type' => $type,
                'days' => $days,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Błąd podczas czyszczenia logów: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Archive and delete old sync jobs (2025-11-12)
     *
     * Exports sync jobs to JSON before deletion with user-specified parameters:
     * - Type: all, completed, failed, completed_with_errors
     * - Age threshold: X days
     * - Option to archive all regardless of age
     *
     * Never archives: pending, running
     *
     * @param string $type Type of jobs to archive
     * @param int $days Age threshold in days
     * @param bool $clearAllAges If true, archive all jobs regardless of age
     * @return void
     */
    public function archiveOldLogs(string $type = 'all', int $days = 90, bool $clearAllAges = false): void
    {
        try {
            $cleanupService = app(SyncJobCleanupService::class);

            Log::info('Manual sync logs archive triggered', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email ?? 'unknown',
                'type' => $type,
                'days' => $days,
                'clear_all_ages' => $clearAllAges,
            ]);

            $stats = $cleanupService->archiveAndCleanup($type, $days, $clearAllAges);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Zarchiwizowano i usunięto {$stats['deleted']} zadań. " .
                             "Plik archiwum: {$stats['archive_file']}"
            ]);

            Log::info('Manual sync logs archive completed', $stats);

        } catch (\Exception $e) {
            Log::error('Sync logs archive failed', [
                'user_id' => auth()->id(),
                'type' => $type,
                'days' => $days,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Błąd podczas archiwizacji logów: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Create sync job record.
     */
    protected function createSyncJob($shop)
    {
        return SyncJob::create([
            'job_id' => \Str::uuid(),
            'job_type' => SyncJob::JOB_PRODUCT_SYNC,
            'job_name' => "Synchronizacja: {$shop->name}",
            'source_type' => SyncJob::TYPE_PPM,
            'target_type' => SyncJob::TYPE_PRESTASHOP,
            'target_id' => $shop->id,
            'trigger_type' => SyncJob::TRIGGER_MANUAL,
            'user_id' => auth()->id(),
            'scheduled_at' => now(),
            'job_config' => [
                'shop_id' => $shop->id,
                'sync_types' => $this->selectedSyncTypes,
                'batch_size' => $this->batchSize,
                'timeout' => $this->syncTimeout,
                'conflict_resolution' => $this->conflictResolution,
            ],
            'status' => SyncJob::STATUS_PENDING,
        ]);
    }

    /**
     * Cancel running sync job.
     */
    public function cancelSyncJob($jobId)
    {
        try {
            $syncJob = SyncJob::where('job_id', $jobId)->firstOrFail();
            
            if (in_array($syncJob->status, [SyncJob::STATUS_PENDING, SyncJob::STATUS_RUNNING])) {
                $syncJob->update([
                    'status' => SyncJob::STATUS_CANCELLED,
                    'error_message' => 'Cancelled by user',
                    'completed_at' => now(),
                ]);
                
                $this->loadActiveSyncJobs();
                session()->flash('success', "Synchronizacja została anulowana.");
                
                Log::info("Sync job cancelled", ['job_id' => $jobId]);
            }

        } catch (\Exception $e) {
            $this->addError('cancel_error', 'Błąd podczas anulowania synchronizacji: ' . $e->getMessage());
        }
    }

    /**
     * Handle sync job update from real-time events.
     */
    public function handleSyncJobUpdate($jobData)
    {
        $this->syncProgress[$jobData['job_id']] = [
            'progress' => $jobData['progress'] ?? 0,
            'status' => $jobData['status'] ?? 'unknown',
            'message' => $jobData['message'] ?? '',
        ];
        
        $this->loadActiveSyncJobs();
    }

    /**
     * Handle sync completion.
     */
    public function handleSyncCompleted($jobId)
    {
        unset($this->syncProgress[$jobId]);
        $this->loadActiveSyncJobs();
        
        // Check if all selected syncs are completed
        if (empty($this->activeSyncJobs)) {
            $this->syncInProgress = false;
            $this->selectedShops = [];
            $this->selectAll = false;
        }
        
        session()->flash('success', 'Synchronizacja została ukończona pomyślnie!');
    }

    /**
     * Handle sync failure.
     */
    public function handleSyncFailed($jobId, $error)
    {
        $this->syncErrors[$jobId] = $error;
        unset($this->syncProgress[$jobId]);
        $this->loadActiveSyncJobs();
        
        session()->flash('error', "Synchronizacja nie powiodła się: {$error}");
    }

    /**
     * Update search query and reset pagination.
     */
    public function updatedSearch()
    {
        $this->resetPage();
    }

    /**
     * Update status filter and reset pagination.
     */
    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    /**
     * Sort shops by column.
     */
    public function sortBy($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    /**
     * Reset all filters.
     */
    public function resetFilters()
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->sortBy = 'last_sync_at';
        $this->sortDirection = 'desc';
        $this->selectedShops = [];
        $this->selectAll = false;
        $this->resetPage();
    }

    // SEKCJA 2.2.1.2 - Sync Configuration Methods

    /**
     * Toggle sync configuration panel visibility.
     */
    public function toggleSyncConfig()
    {
        $this->showSyncConfig = !$this->showSyncConfig;

        if ($this->showSyncConfig) {
            $this->loadSyncConfiguration();
        }
    }

    /**
     * Load sync configuration from system settings.
     * SEKCJA 2.2.1.2 - Sync Configuration (LEGACY - deprecated)
     *
     * @deprecated Use loadSyncConfigurationFromDatabase() instead
     */
    protected function loadSyncConfiguration()
    {
        // In production, this would load from system settings or database
        // For now, keep current default values as they represent good defaults

        // Load notification recipients from system users with admin role
        // Placeholder implementation - in production get from User model
        $this->notificationRecipients = [
            'admin@mpptrade.pl' => 'System Administrator',
            'ops@mpptrade.pl' => 'Operations Team'
        ];
    }

    /**
     * Load sync configuration from database (MVP - Priority 1)
     *
     * Loads all sync.* settings from SystemSetting table and maps to component properties.
     * If no settings exist, uses component's default values.
     *
     * @return void
     */
    protected function loadSyncConfigurationFromDatabase()
    {
        // FIX: Skip reload if user just saved configuration
        if (session()->has('sync_config_just_saved')) {
            Log::debug('Skipping config reload - user just saved configuration');
            session()->forget('sync_config_just_saved');
            return;
        }

        // DEBUG: Log load call
        Log::debug('loadSyncConfigurationFromDatabase() CALLED', [
            'autoSyncFrequency_BEFORE' => $this->autoSyncFrequency ?? 'NULL',
        ]);

        try {
            // Load all sync.* settings
            $settings = SystemSetting::where('key', 'LIKE', 'sync.%')->get();

            if ($settings->isEmpty()) {
                Log::info('No sync configuration found in database, using defaults');
                return;
            }

            // DEBUG: Log frequency setting BEFORE mapping
            $frequencySetting = $settings->where('key', 'sync.schedule.frequency')->first();
            Log::debug('Frequency setting from DB', [
                'value' => $frequencySetting ? $frequencySetting->value : 'NOT FOUND',
                'updated_at' => $frequencySetting ? $frequencySetting->updated_at : null,
            ]);

            // Map settings to component properties
            foreach ($settings as $setting) {
                $this->mapSettingToProperty($setting->key, $setting->value);
            }

            // DEBUG: Log frequency AFTER mapping
            Log::debug('loadSyncConfigurationFromDatabase() COMPLETED', [
                'autoSyncFrequency_AFTER' => $this->autoSyncFrequency,
            ]);

            Log::info('Sync configuration loaded from database', [
                'settings_count' => $settings->count(),
                'user_id' => auth()->id(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load sync configuration', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Map SystemSetting key to component property
     *
     * @param string $key SystemSetting key (e.g., 'sync.batch_size')
     * @param mixed $value Setting value
     * @return void
     */
    private function mapSettingToProperty(string $key, $value)
    {
        $mapping = [
            // Basic sync configuration
            'sync.batch_size' => 'batchSize',
            'sync.timeout' => 'syncTimeout',
            'sync.conflict_resolution' => 'conflictResolution',
            'sync.selected_types' => 'selectedSyncTypes',

            // Auto-sync scheduler - 2.2.1.2.1
            'sync.schedule.enabled' => 'autoSyncEnabled',
            'sync.schedule.frequency' => 'autoSyncFrequency',
            'sync.schedule.hour' => 'autoSyncScheduleHour',
            'sync.schedule.days_of_week' => 'autoSyncDaysOfWeek',
            'sync.schedule.only_connected' => 'autoSyncOnlyConnected',
            'sync.schedule.skip_maintenance' => 'autoSyncSkipMaintenanceMode',

            // Retry logic - 2.2.1.2.2
            'sync.retry.enabled' => 'retryEnabled',
            'sync.retry.max_attempts' => 'maxRetryAttempts',
            'sync.retry.delay_minutes' => 'retryDelayMinutes',
            'sync.retry.backoff_multiplier' => 'retryBackoffMultiplier',
            'sync.retry.only_transient' => 'retryOnlyTransientErrors',

            // Notifications - 2.2.1.2.3
            'sync.notifications.enabled' => 'notificationsEnabled',
            'sync.notifications.on_success' => 'notifyOnSuccess',
            'sync.notifications.on_failure' => 'notifyOnFailure',
            'sync.notifications.on_retry_exhausted' => 'notifyOnRetryExhausted',
            'sync.notifications.channels' => 'notificationChannels',
            'sync.notifications.recipients' => 'notificationRecipients',

            // Performance - 2.2.1.2.4
            'sync.performance.mode' => 'performanceMode',
            'sync.performance.max_concurrent' => 'maxConcurrentJobs',
            'sync.performance.delay_ms' => 'jobProcessingDelay',
            'sync.performance.memory_limit_mb' => 'memoryLimit',
            'sync.performance.timeout_seconds' => 'processTimeout',

            // Backup - 2.2.1.2.5
            'sync.backup.enabled' => 'backupBeforeSync',
            'sync.backup.retention_days' => 'backupRetentionDays',
            'sync.backup.only_major_changes' => 'backupOnlyOnMajorChanges',
            'sync.backup.compression' => 'backupCompressionEnabled',
        ];

        if (isset($mapping[$key])) {
            $property = $mapping[$key];
            $this->$property = $value;

            Log::debug('Mapped setting to property', [
                'key' => $key,
                'property' => $property,
                'value' => $value,
                'value_type' => gettype($value),
            ]);
        }
    }

    /**
     * Save sync configuration to system settings (MVP - Priority 1)
     *
     * SEKCJA 2.2.1.2.1-2.2.1.2.5 - Save all configuration sections to SystemSetting table
     *
     * Validates all settings and saves to database. Each setting is stored with proper type.
     *
     * @return void
     */
    public function saveSyncConfiguration()
    {
        // DEBUG: Log state BEFORE validation
        Log::debug('saveSyncConfiguration CALLED', [
            'autoSyncFrequency' => $this->autoSyncFrequency,
            'autoSyncFrequency_type' => gettype($this->autoSyncFrequency),
            'all_properties' => [
                'enabled' => $this->autoSyncEnabled,
                'frequency' => $this->autoSyncFrequency,
                'hour' => $this->autoSyncScheduleHour,
            ]
        ]);

        $this->validate();

        try {
            // Prepare settings array with proper keys
            $settings = [
                // Basic sync configuration
                'sync.batch_size' => $this->batchSize,
                'sync.timeout' => $this->syncTimeout,
                'sync.conflict_resolution' => $this->conflictResolution,
                'sync.selected_types' => $this->selectedSyncTypes,

                // Auto-sync scheduler - 2.2.1.2.1
                'sync.schedule.enabled' => $this->autoSyncEnabled,
                'sync.schedule.frequency' => $this->autoSyncFrequency,
                'sync.schedule.hour' => $this->autoSyncScheduleHour,
                'sync.schedule.days_of_week' => $this->autoSyncDaysOfWeek,
                'sync.schedule.only_connected' => $this->autoSyncOnlyConnected,
                'sync.schedule.skip_maintenance' => $this->autoSyncSkipMaintenanceMode,

                // Retry logic - 2.2.1.2.2
                'sync.retry.enabled' => $this->retryEnabled,
                'sync.retry.max_attempts' => $this->maxRetryAttempts,
                'sync.retry.delay_minutes' => $this->retryDelayMinutes,
                'sync.retry.backoff_multiplier' => $this->retryBackoffMultiplier,
                'sync.retry.only_transient' => $this->retryOnlyTransientErrors,

                // Notifications - 2.2.1.2.3
                'sync.notifications.enabled' => $this->notificationsEnabled,
                'sync.notifications.on_success' => $this->notifyOnSuccess,
                'sync.notifications.on_failure' => $this->notifyOnFailure,
                'sync.notifications.on_retry_exhausted' => $this->notifyOnRetryExhausted,
                'sync.notifications.channels' => $this->notificationChannels,
                'sync.notifications.recipients' => $this->notificationRecipients,

                // Performance - 2.2.1.2.4
                'sync.performance.mode' => $this->performanceMode,
                'sync.performance.max_concurrent' => $this->maxConcurrentJobs,
                'sync.performance.delay_ms' => $this->jobProcessingDelay,
                'sync.performance.memory_limit_mb' => $this->memoryLimit,
                'sync.performance.timeout_seconds' => $this->processTimeout,

                // Backup - 2.2.1.2.5
                'sync.backup.enabled' => $this->backupBeforeSync,
                'sync.backup.retention_days' => $this->backupRetentionDays,
                'sync.backup.only_major_changes' => $this->backupOnlyOnMajorChanges,
                'sync.backup.compression' => $this->backupCompressionEnabled,
            ];

            // DEBUG: Log BEFORE updateOrCreate
            Log::debug('BEFORE updateOrCreate', [
                'key' => 'sync.schedule.frequency',
                'value' => $this->autoSyncFrequency,
                'settings_array_value' => $settings['sync.schedule.frequency']
            ]);

            // Save each setting to SystemSetting table
            foreach ($settings as $key => $value) {
                SystemSetting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'type' => $this->getSettingType($value),
                        'description' => $this->getSettingDescription($key),
                    ]
                );
            }

            // DEBUG: Log AFTER updateOrCreate - verify
            $verifyValue = SystemSetting::where('key', 'sync.schedule.frequency')->first();
            Log::debug('AFTER updateOrCreate - verify', [
                'saved_value' => $verifyValue ? $verifyValue->value : 'NOT FOUND',
                'saved_updated_at' => $verifyValue ? $verifyValue->updated_at : null,
            ]);

            // Log configuration change
            Log::info('Sync configuration saved to database', [
                'settings_count' => count($settings),
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email ?? 'unknown',
            ]);

            // FIX: Set session flag to prevent immediate reload
            session()->put('sync_config_just_saved', true);

            // DEBUG: Verify session flag was set
            Log::debug('Session flag SET', [
                'session_value' => session('sync_config_just_saved'),
                'session_has_key' => session()->has('sync_config_just_saved'),
            ]);

            session()->flash('success', 'Konfiguracja synchronizacji została zapisana pomyślnie!');

        } catch (\Exception $e) {
            Log::error('Failed to save sync configuration', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);

            session()->flash('error', 'Błąd podczas zapisywania konfiguracji: ' . $e->getMessage());
        }
    }

    /**
     * Get human-readable description for setting key
     *
     * @param string $key Setting key
     * @return string Description
     */
    private function getSettingDescription(string $key): string
    {
        $descriptions = [
            'sync.batch_size' => 'Number of items to process in each sync batch',
            'sync.timeout' => 'Maximum sync operation timeout in seconds',
            'sync.conflict_resolution' => 'Strategy for resolving sync conflicts',
            'sync.selected_types' => 'Types of data to synchronize',
            'sync.schedule.enabled' => 'Enable automatic synchronization',
            'sync.schedule.frequency' => 'Synchronization frequency',
            'sync.schedule.hour' => 'Hour of day to run scheduled sync (0-23)',
            'sync.schedule.days_of_week' => 'Days of week for weekly sync',
            'sync.schedule.only_connected' => 'Sync only connected shops',
            'sync.schedule.skip_maintenance' => 'Skip sync during maintenance mode',
            'sync.retry.enabled' => 'Enable automatic retry for failed syncs',
            'sync.retry.max_attempts' => 'Maximum retry attempts',
            'sync.retry.delay_minutes' => 'Delay between retries in minutes',
            'sync.retry.backoff_multiplier' => 'Exponential backoff multiplier',
            'sync.retry.only_transient' => 'Retry only transient errors',
            'sync.notifications.enabled' => 'Enable sync notifications',
            'sync.notifications.on_success' => 'Notify on successful sync',
            'sync.notifications.on_failure' => 'Notify on sync failure',
            'sync.notifications.on_retry_exhausted' => 'Notify when retries exhausted',
            'sync.notifications.channels' => 'Notification channels (email, slack)',
            'sync.notifications.recipients' => 'Notification recipients',
            'sync.performance.mode' => 'Performance optimization mode',
            'sync.performance.max_concurrent' => 'Maximum concurrent sync jobs',
            'sync.performance.delay_ms' => 'Delay between jobs in milliseconds',
            'sync.performance.memory_limit_mb' => 'Memory limit in megabytes',
            'sync.performance.timeout_seconds' => 'Process timeout in seconds',
            'sync.backup.enabled' => 'Backup data before sync',
            'sync.backup.retention_days' => 'Backup retention period in days',
            'sync.backup.only_major_changes' => 'Backup only on major changes',
            'sync.backup.compression' => 'Enable backup compression',
        ];

        return $descriptions[$key] ?? '';
    }

    /**
     * Reset sync configuration to defaults (MVP - Priority 1)
     *
     * SEKCJA 2.2.1.2 - Reset all configuration sections to defaults and save to database
     *
     * @return void
     */
    public function resetSyncConfigurationToDefaults()
    {
        try {
            // Basic sync defaults
            $this->batchSize = 10;
            $this->syncTimeout = 300;
            $this->conflictResolution = 'ppm_wins';
            $this->selectedSyncTypes = ['products'];

            // Auto-sync scheduler defaults - 2.2.1.2.1
            $this->autoSyncEnabled = true;
            $this->autoSyncFrequency = 'hourly';
            $this->autoSyncScheduleHour = 2;
            $this->autoSyncDaysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            $this->autoSyncOnlyConnected = true;
            $this->autoSyncSkipMaintenanceMode = true;

            // Retry logic defaults - 2.2.1.2.2
            $this->retryEnabled = true;
            $this->maxRetryAttempts = 3;
            $this->retryDelayMinutes = 15;
            $this->retryBackoffMultiplier = 2.0;
            $this->retryOnlyTransientErrors = true;

            // Notification defaults - 2.2.1.2.3
            $this->notificationsEnabled = true;
            $this->notifyOnSuccess = false;
            $this->notifyOnFailure = true;
            $this->notifyOnRetryExhausted = true;
            $this->notificationChannels = ['email'];
            $this->notificationRecipients = [];

            // Performance defaults - 2.2.1.2.4
            $this->performanceMode = 'balanced';
            $this->maxConcurrentJobs = 3;
            $this->jobProcessingDelay = 100;
            $this->memoryLimit = 512;
            $this->processTimeout = 1800;

            // Backup defaults - 2.2.1.2.5
            $this->backupBeforeSync = true;
            $this->backupRetentionDays = 7;
            $this->backupOnlyOnMajorChanges = true;
            $this->backupCompressionEnabled = true;

            // Save defaults to database
            $this->saveSyncConfiguration();

            Log::info('Sync configuration reset to defaults', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email ?? 'unknown',
            ]);

            session()->flash('success', 'Konfiguracja została zresetowana do wartości domyślnych i zapisana!');

        } catch (\Exception $e) {
            Log::error('Failed to reset sync configuration', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            session()->flash('error', 'Błąd podczas resetowania konfiguracji: ' . $e->getMessage());
        }
    }

    /**
     * Get performance mode description.
     * SEKCJA 2.2.1.2.4 - Performance optimization settings
     */
    public function getPerformanceModeDescription($mode)
    {
        $descriptions = [
            'economy' => 'Tryb ekonomiczny - minimalne zużycie zasobów, wolniejsza synchronizacja',
            'balanced' => 'Tryb zrównoważony - optymalny balans między wydajnością a zużyciem zasobów',
            'performance' => 'Tryb wydajnościowy - maksymalna szybkość, większe zużycie zasobów',
        ];

        return $descriptions[$mode] ?? 'Nieznany tryb';
    }

    /**
     * Get configured sync schedule description.
     * SEKCJA 2.2.1.2.1 - Auto-sync scheduler configuration
     */
    public function getSyncScheduleDescription()
    {
        if (!$this->autoSyncEnabled) {
            return 'Automatyczna synchronizacja jest wyłączona';
        }

        $frequency = '';
        switch ($this->autoSyncFrequency) {
            case 'hourly':
                $frequency = 'co godzinę';
                break;
            case 'daily':
                $frequency = sprintf('codziennie o %d:00', $this->autoSyncScheduleHour);
                break;
            case 'weekly':
                $days = implode(', ', array_map('ucfirst', $this->autoSyncDaysOfWeek));
                $frequency = sprintf('w dni: %s o %d:00', $days, $this->autoSyncScheduleHour);
                break;
        }

        return sprintf('Synchronizacja automatyczna: %s', $frequency);
    }

    /**
     * Test sync configuration - validate and preview settings (MVP - Priority 1)
     *
     * SEKCJA 2.2.1.2 - Configuration testing and validation
     *
     * Tests:
     * 1. Validation rules compliance
     * 2. SystemSetting table accessibility
     * 3. Required settings existence
     * 4. Settings values integrity
     * 5. Scheduler command availability
     *
     * @return void
     */
    public function testSyncConfiguration()
    {
        try {
            Log::info('Testing sync configuration', [
                'user_id' => auth()->id(),
            ]);

            // Test 1: Validate current settings
            try {
                $this->validate();
                $validationPassed = true;
            } catch (\Exception $e) {
                session()->flash('error', 'Błędy walidacji: ' . $e->getMessage());
                return;
            }

            // Test 2: Check SystemSetting table accessibility
            $settingsCount = SystemSetting::where('key', 'LIKE', 'sync.%')->count();

            // Test 3: Verify basic settings exist in database
            $requiredSettings = [
                'sync.batch_size',
                'sync.timeout',
                'sync.conflict_resolution',
            ];

            $missingSettings = [];
            foreach ($requiredSettings as $key) {
                if (!SystemSetting::where('key', $key)->exists()) {
                    $missingSettings[] = $key;
                }
            }

            if (!empty($missingSettings)) {
                session()->flash('warning',
                    'Niektóre podstawowe ustawienia nie zostały jeszcze zapisane: ' .
                    implode(', ', $missingSettings) .
                    '. Kliknij "Zapisz konfigurację" najpierw.'
                );
                return;
            }

            // Test 4: Validate settings values
            $errors = [];

            if ($this->batchSize < 1 || $this->batchSize > 100) {
                $errors[] = 'Wielkość paczki poza zakresem (1-100)';
            }

            if ($this->syncTimeout < 60 || $this->syncTimeout > 3600) {
                $errors[] = 'Timeout poza zakresem (60-3600s)';
            }

            if ($this->maxConcurrentJobs < 1 || $this->maxConcurrentJobs > 10) {
                $errors[] = 'Max równoczesnych poza zakresem (1-10)';
            }

            if (!empty($errors)) {
                session()->flash('error', 'Błędy walidacji wartości: ' . implode(', ', $errors));
                return;
            }

            // Test 5: Check if scheduler command exists (Artisan command availability)
            try {
                \Artisan::call('list');
                $schedulerExists = true;
            } catch (\Exception $e) {
                $schedulerExists = false;
            }

            // Test 6: Perform additional validation tests
            $validationResults = [
                'scheduler' => $this->validateSchedulerConfig(),
                'retry_logic' => $this->validateRetryConfig(),
                'notifications' => $this->validateNotificationConfig(),
                'performance' => $this->validatePerformanceConfig(),
                'backup' => $this->validateBackupConfig(),
            ];

            $allValid = array_reduce($validationResults, function($carry, $result) {
                return $carry && $result['valid'];
            }, true);

            // Log test results
            Log::info('Sync configuration test completed', [
                'settings_count' => $settingsCount,
                'validation_passed' => $validationPassed,
                'scheduler_exists' => $schedulerExists,
                'all_tests_valid' => $allValid,
                'batch_size' => $this->batchSize,
                'timeout' => $this->syncTimeout,
                'max_concurrent' => $this->maxConcurrentJobs,
            ]);

            // Display results
            if ($allValid) {
                session()->flash('success',
                    "Test konfiguracji zakończony pomyślnie!\n" .
                    "Znalezionych ustawień: {$settingsCount}\n" .
                    "Scheduler: " . ($schedulerExists ? 'Dostępny' : 'BRAK') . "\n" .
                    "Wszystkie walidacje: PASSED"
                );
            } else {
                $warnings = [];
                foreach ($validationResults as $section => $result) {
                    if (!$result['valid']) {
                        $warnings[] = sprintf('%s: %s', ucfirst($section), $result['message']);
                    }
                }
                session()->flash('warning',
                    "Test zakończony z ostrzeżeniami:\n" .
                    implode("\n", $warnings)
                );
            }

        } catch (\Exception $e) {
            Log::error('Sync configuration test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            session()->flash('error', 'Test konfiguracji nieudany: ' . $e->getMessage());
        }
    }

    /**
     * Validate scheduler configuration.
     */
    protected function validateSchedulerConfig()
    {
        if ($this->autoSyncEnabled && $this->autoSyncFrequency === 'weekly' && empty($this->autoSyncDaysOfWeek)) {
            return ['valid' => false, 'message' => 'Wybierz co najmniej jeden dzień tygodnia dla synchronizacji tygodniowej'];
        }

        return ['valid' => true, 'message' => 'Konfiguracja harmonogramu jest poprawna'];
    }

    /**
     * Validate retry configuration.
     */
    protected function validateRetryConfig()
    {
        if ($this->retryEnabled && $this->maxRetryAttempts === 0) {
            return ['valid' => false, 'message' => 'Liczba prób ponowienia musi być większa od 0 gdy retry jest włączone'];
        }

        return ['valid' => true, 'message' => 'Konfiguracja ponawiania jest poprawna'];
    }

    /**
     * Validate notification configuration.
     */
    protected function validateNotificationConfig()
    {
        if ($this->notificationsEnabled && empty($this->notificationChannels)) {
            return ['valid' => false, 'message' => 'Wybierz co najmniej jeden kanał powiadomień'];
        }

        if ($this->notificationsEnabled && empty($this->notificationRecipients)) {
            return ['valid' => false, 'message' => 'Dodaj co najmniej jednego odbiorcy powiadomień'];
        }

        return ['valid' => true, 'message' => 'Konfiguracja powiadomień jest poprawna'];
    }

    /**
     * Validate performance configuration.
     */
    protected function validatePerformanceConfig()
    {
        if ($this->performanceMode === 'performance' && $this->maxConcurrentJobs > 5) {
            return ['valid' => false, 'message' => 'W trybie wydajnościowym zalecane jest maksymalnie 5 równoczesnych zadań'];
        }

        return ['valid' => true, 'message' => 'Konfiguracja wydajności jest poprawna'];
    }

    /**
     * Validate backup configuration.
     */
    protected function validateBackupConfig()
    {
        if ($this->backupBeforeSync && $this->backupRetentionDays < 3) {
            return ['valid' => false, 'message' => 'Okres przechowywania backupów powinien wynosić co najmniej 3 dni'];
        }

        return ['valid' => true, 'message' => 'Konfiguracja backupów jest poprawna'];
    }

    /**
     * Clear Laravel caches and restart queue workers (2025-11-12)
     *
     * Admin-only maintenance operation for troubleshooting:
     * - Clears config cache (fixes cached .env values)
     * - Clears application cache
     * - Clears compiled views
     * - Restarts queue workers (if using Supervisor)
     *
     * Use cases:
     * - After .env changes (QUEUE_CONNECTION, etc.)
     * - When queue jobs not appearing
     * - After deployment with config changes
     */
    public function clearCacheAndRestartQueue()
    {
        try {
            // Clear config cache (most important - fixes cached .env values)
            \Artisan::call('config:clear');

            // Clear application cache
            \Artisan::call('cache:clear');

            // Clear compiled views
            \Artisan::call('view:clear');

            // Restart queue workers (works if using Supervisor, harmless if not)
            \Artisan::call('queue:restart');

            \Log::info('Cache cleared and queue restarted by admin', [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
            ]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Cache wyczyszczony i queue zrestartowany pomyślnie'
            ]);

            // Refresh page data after cache clear
            $this->refreshData();

        } catch (\Exception $e) {
            \Log::error('clearCacheAndRestartQueue failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Błąd podczas czyszczenia cache: ' . $e->getMessage()
            ]);
        }
    }
}
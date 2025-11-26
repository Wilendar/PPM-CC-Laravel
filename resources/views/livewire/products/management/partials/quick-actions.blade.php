{{-- Quick Actions Panel --}}
<div class="enterprise-card p-6"
     x-data="syncStatusTracker(@entangle('activeJobStatus'), @entangle('activeJobType'), @entangle('jobResult'), @entangle('jobCreatedAt'))">

    <h4 class="text-lg font-bold text-dark-primary mb-6 flex items-center">
        <i class="fas fa-bolt text-mpp-orange mr-2"></i>
        Szybkie akcje
    </h4>

    <div class="space-y-4">
        {{-- ========================================== --}}
        {{-- SYNC STATUS INDICATOR (shows during job)  --}}
        {{-- FIX 2025-11-25: Use x-show instead of template x-if to prevent --}}
        {{-- duplicate elements during Livewire morph                       --}}
        {{-- ========================================== --}}

        {{-- RUNNING STATE: Animated progress bar --}}
        <div class="sync-status-container sync-status-running"
             x-show="isJobRunning"
             x-cloak>
            <div class="sync-status-header">
                <i class="fas fa-sync fa-spin text-mpp-orange mr-2"></i>
                <span class="font-bold text-mpp-orange">Trwa aktualizacja</span>
            </div>
            <div class="sync-progress-bar-container">
                <div class="sync-progress-bar" :style="`width: ${progress}%`"></div>
            </div>
            <div class="sync-status-time text-sm text-dark-secondary mt-2">
                <span x-text="statusText"></span>
                <span class="ml-2" x-show="remainingSeconds > 0">
                    (<span x-text="remainingSeconds"></span>s)
                </span>
            </div>
        </div>

        {{-- SUCCESS STATE --}}
        <div class="sync-status-container sync-status-success"
             x-show="showCompletionStatus && completionResult === 'success'"
             x-cloak>
            <div class="sync-status-header">
                <i class="fas fa-check-circle text-green-500 mr-2 text-xl"></i>
                <span class="font-bold text-green-500">SUKCES</span>
            </div>
            <div class="text-sm text-dark-secondary mt-2">
                Synchronizacja zakonczona pomyslnie
            </div>
        </div>

        {{-- ERROR STATE --}}
        <div class="sync-status-container sync-status-error"
             x-show="showCompletionStatus && completionResult === 'error'"
             x-cloak>
            <div class="sync-status-header">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2 text-xl"></i>
                <span class="font-bold text-red-500">BLAD</span>
            </div>
            <div class="text-sm text-dark-secondary mt-2">
                Wystapil blad podczas synchronizacji
            </div>
        </div>

        {{-- ========================================== --}}
        {{-- SAVE BUTTON - Always visible, changes mode --}}
        {{-- ========================================== --}}
        @include('livewire.products.management.partials.actions.save-and-close-button')

        {{-- ========================================== --}}
        {{-- NORMAL BUTTONS - Hidden during job        --}}
        {{-- FIX 2025-11-25: Use x-show instead of template x-if --}}
        {{-- ========================================== --}}
        <div class="space-y-4" x-show="!isJobRunning && !showCompletionStatus" x-cloak>
            {{-- ETAP_13.2: Aktualizuj sklepy (ALL shops export) --}}
            @if($isEditMode && !empty($exportedShops))
                <button
                    type="button"
                    wire:click="bulkUpdateShops"
                    class="btn-enterprise-secondary w-full py-3"
                    :disabled="$wire.activeJobStatus === 'pending' || $wire.activeJobStatus === 'processing'">
                    <i class="fas fa-cloud-upload-alt mr-2"></i>
                    Aktualizuj sklepy
                </button>
            @endif

            {{-- ETAP_13.2: Wczytaj ze sklepow (ALL shops import) --}}
            @if($isEditMode && !empty($exportedShops))
                <button
                    type="button"
                    wire:click="bulkPullFromShops"
                    class="btn-enterprise-secondary w-full py-3"
                    :disabled="$wire.activeJobStatus === 'pending' || $wire.activeJobStatus === 'processing'">
                    <i class="fas fa-cloud-download-alt mr-2"></i>
                    Wczytaj ze sklepow
                </button>
            @endif

            {{-- Cancel Button --}}
            @include('livewire.products.management.partials.actions.cancel-link')
        </div>
    </div>
</div>

{{-- Alpine.js Component for Sync Status Tracking --}}
<script>
document.addEventListener('alpine:init', () => {
    // Guard against double registration (use window flag - survives Livewire morph)
    if (window._syncStatusTrackerRegistered) return;
    window._syncStatusTrackerRegistered = true;

    Alpine.data('syncStatusTracker', (activeJobStatus, activeJobType, jobResult, jobCreatedAt) => ({
        activeJobStatus: activeJobStatus,
        activeJobType: activeJobType,
        jobResult: jobResult,
        jobCreatedAt: jobCreatedAt,
        progress: 0,
        remainingSeconds: 0,
        showCompletionStatus: false,
        completionResult: null,
        completionTimeout: null,
        estimatedDuration: 60, // seconds (max JOB execution time)

        init() {
            // Use window-level tracking to prevent duplicate completion across Livewire morphs
            // Key: jobCreatedAt timestamp (unique per job)
            const jobKey = this.jobCreatedAt || 'no-job';
            window._syncCompletionShown = window._syncCompletionShown || {};

            // Watch for job status changes (single watcher handles everything)
            this.$watch('activeJobStatus', (newStatus, oldStatus) => {
                console.log('[SyncStatus] activeJobStatus changed:', oldStatus, '->', newStatus, 'jobKey:', jobKey);

                if (newStatus === 'completed' || newStatus === 'failed') {
                    // Job finished - show completion status ONCE per unique job
                    const currentJobKey = this.jobCreatedAt || 'no-job';
                    if (!window._syncCompletionShown[currentJobKey]) {
                        window._syncCompletionShown[currentJobKey] = true;
                        this.handleJobCompletion(newStatus);
                        // Cleanup old keys (keep last 10)
                        const keys = Object.keys(window._syncCompletionShown);
                        if (keys.length > 10) {
                            delete window._syncCompletionShown[keys[0]];
                        }
                    }
                } else if (newStatus === 'pending' || newStatus === 'processing') {
                    this.startProgressTracking();
                } else {
                    // null or other - reset state
                    this.resetState();
                }
            });

            // Initialize if job is already running on mount
            if (this.activeJobStatus === 'pending' || this.activeJobStatus === 'processing') {
                this.startProgressTracking();
            }
        },

        get isJobRunning() {
            return this.activeJobStatus === 'pending' || this.activeJobStatus === 'processing';
        },

        get statusText() {
            if (this.activeJobType === 'sync') {
                return 'Aktualizowanie sklepow...';
            } else if (this.activeJobType === 'pull') {
                return 'Pobieranie danych...';
            }
            return 'Przetwarzanie...';
        },

        resetState() {
            this.showCompletionStatus = false;
            this.completionResult = null;
            this.progress = 0;
            this.remainingSeconds = 0;
            if (this.completionTimeout) {
                clearTimeout(this.completionTimeout);
                this.completionTimeout = null;
            }
        },

        startProgressTracking() {
            this.showCompletionStatus = false;
            this.completionResult = null;

            if (this.completionTimeout) {
                clearTimeout(this.completionTimeout);
                this.completionTimeout = null;
            }

            // Calculate progress based on elapsed time
            const startTime = this.jobCreatedAt ? new Date(this.jobCreatedAt).getTime() : Date.now();
            const updateProgress = () => {
                if (!this.isJobRunning) return;

                const elapsed = (Date.now() - startTime) / 1000;
                // Progress formula: approaches 95% asymptotically
                this.progress = Math.min(95, (elapsed / this.estimatedDuration) * 100);
                this.remainingSeconds = Math.max(0, Math.round(this.estimatedDuration - elapsed));

                if (this.isJobRunning) {
                    setTimeout(updateProgress, 500);
                }
            };
            updateProgress();
        },

        handleJobCompletion(status) {
            console.log('[SyncStatus] handleJobCompletion:', status, 'jobResult:', this.jobResult);

            this.progress = 100;
            this.remainingSeconds = 0;
            this.showCompletionStatus = true;
            this.completionResult = (status === 'completed') ? (this.jobResult || 'success') : 'error';

            // Clear after 5 seconds
            this.completionTimeout = setTimeout(() => {
                this.resetState();
            }, 5000);
        }
    }));
});
</script>

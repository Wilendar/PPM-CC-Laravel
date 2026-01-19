{{-- Quick Actions Panel --}}
<div class="enterprise-card p-6"
     x-data="quickActionsTracker(
        @entangle('activeJobStatus'),
        @entangle('activeJobType'),
        @entangle('jobResult'),
        @entangle('jobCreatedAt'),
        @entangle('activeErpJobStatus'),
        @entangle('activeErpJobType'),
        @entangle('erpJobResult'),
        @entangle('erpJobCreatedAt')
     )">

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
        {{-- ETAP_08.6: ERP SYNC STATUS INDICATORS     --}}
        {{-- ========================================== --}}

        {{-- ERP RUNNING STATE: Animated progress bar --}}
        <div class="sync-status-container sync-status-running erp-sync-status"
             x-show="erpIsJobRunning"
             x-cloak>
            <div class="sync-status-header">
                <i class="fas fa-sync fa-spin text-blue-400 mr-2"></i>
                <span class="font-bold text-blue-400">Synchronizacja ERP</span>
            </div>
            <div class="sync-progress-bar-container">
                <div class="sync-progress-bar erp-progress-bar" :style="`width: ${erpProgress}%`"></div>
            </div>
            <div class="sync-status-time text-sm text-dark-secondary mt-2">
                <span x-text="erpStatusText"></span>
                <span class="ml-2" x-show="erpRemainingSeconds > 0">
                    (<span x-text="erpRemainingSeconds"></span>s)
                </span>
            </div>
        </div>

        {{-- ERP SUCCESS STATE --}}
        <div class="sync-status-container sync-status-success erp-sync-status"
             x-show="erpShowCompletionStatus && erpCompletionResult === 'success'"
             x-cloak>
            <div class="sync-status-header">
                <i class="fas fa-check-circle text-green-500 mr-2 text-xl"></i>
                <span class="font-bold text-green-500">ERP SUKCES</span>
            </div>
            <div class="text-sm text-dark-secondary mt-2">
                Synchronizacja ERP zakonczona pomyslnie
            </div>
        </div>

        {{-- ERP ERROR STATE --}}
        <div class="sync-status-container sync-status-error erp-sync-status"
             x-show="erpShowCompletionStatus && erpCompletionResult === 'error'"
             x-cloak>
            <div class="sync-status-header">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2 text-xl"></i>
                <span class="font-bold text-red-500">ERP BLAD</span>
            </div>
            <div class="text-sm text-dark-secondary mt-2">
                Wystapil blad podczas synchronizacji ERP
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
        <div class="space-y-4" x-show="!isJobRunning && !showCompletionStatus && !erpIsJobRunning && !erpShowCompletionStatus" x-cloak>
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

{{-- Alpine.js Component for Combined Sync Status Tracking (PrestaShop + ERP) --}}
<script>
document.addEventListener('alpine:init', () => {
    // Guard against double registration (use window flag - survives Livewire morph)
    if (window._quickActionsTrackerRegistered) return;
    window._quickActionsTrackerRegistered = true;

    // ==========================================
    // ETAP_08.6: COMBINED QUICK ACTIONS TRACKER
    // Handles both PrestaShop and ERP sync status
    // ==========================================
    Alpine.data('quickActionsTracker', (
        activeJobStatus, activeJobType, jobResult, jobCreatedAt,
        activeErpJobStatus, activeErpJobType, erpJobResult, erpJobCreatedAt
    ) => ({
        // PrestaShop job tracking
        activeJobStatus: activeJobStatus,
        activeJobType: activeJobType,
        jobResult: jobResult,
        jobCreatedAt: jobCreatedAt,
        progress: 0,
        remainingSeconds: 0,
        showCompletionStatus: false,
        completionResult: null,
        completionTimeout: null,
        estimatedDuration: 60,

        // ERP job tracking (prefixed to avoid conflicts)
        activeErpJobStatus: activeErpJobStatus,
        activeErpJobType: activeErpJobType,
        erpJobResult: erpJobResult,
        erpJobCreatedAt: erpJobCreatedAt,
        erpProgress: 0,
        erpRemainingSeconds: 0,
        erpShowCompletionStatus: false,
        erpCompletionResult: null,
        erpCompletionTimeout: null,
        erpEstimatedDuration: 45,

        init() {
            // === PrestaShop job watcher ===
            window._syncCompletionShown = window._syncCompletionShown || {};
            this.$watch('activeJobStatus', (newStatus, oldStatus) => {
                if (newStatus === 'completed' || newStatus === 'failed') {
                    const currentJobKey = this.jobCreatedAt || 'no-job';
                    if (!window._syncCompletionShown[currentJobKey]) {
                        window._syncCompletionShown[currentJobKey] = true;
                        this.handleJobCompletion(newStatus);
                    }
                } else if (newStatus === 'pending' || newStatus === 'processing') {
                    this.startProgressTracking();
                } else {
                    this.resetState();
                }
            });

            // === ERP job watcher ===
            window._erpSyncCompletionShown = window._erpSyncCompletionShown || {};
            this.$watch('activeErpJobStatus', (newStatus, oldStatus) => {
                if (newStatus === 'completed' || newStatus === 'failed') {
                    const currentJobKey = this.erpJobCreatedAt || 'no-erp-job';
                    if (!window._erpSyncCompletionShown[currentJobKey]) {
                        window._erpSyncCompletionShown[currentJobKey] = true;
                        this.handleErpJobCompletion(newStatus);
                    }
                } else if (newStatus === 'pending' || newStatus === 'running') {
                    this.startErpProgressTracking();
                } else {
                    this.resetErpState();
                }
            });

            // Initialize if jobs already running
            if (this.activeJobStatus === 'pending' || this.activeJobStatus === 'processing') {
                this.startProgressTracking();
            }
            if (this.activeErpJobStatus === 'pending' || this.activeErpJobStatus === 'running') {
                this.startErpProgressTracking();
            }
        },

        // === PrestaShop getters and methods ===
        get isJobRunning() {
            return this.activeJobStatus === 'pending' || this.activeJobStatus === 'processing';
        },

        get statusText() {
            if (this.activeJobType === 'sync') return 'Aktualizowanie sklepow...';
            if (this.activeJobType === 'pull') return 'Pobieranie danych...';
            return 'Przetwarzanie...';
        },

        resetState() {
            this.showCompletionStatus = false;
            this.completionResult = null;
            this.progress = 0;
            this.remainingSeconds = 0;
            if (this.completionTimeout) { clearTimeout(this.completionTimeout); this.completionTimeout = null; }
        },

        startProgressTracking() {
            this.showCompletionStatus = false;
            this.completionResult = null;
            if (this.completionTimeout) { clearTimeout(this.completionTimeout); this.completionTimeout = null; }
            const startTime = this.jobCreatedAt ? new Date(this.jobCreatedAt).getTime() : Date.now();
            const updateProgress = () => {
                if (!this.isJobRunning) return;
                const elapsed = (Date.now() - startTime) / 1000;
                this.progress = Math.min(95, (elapsed / this.estimatedDuration) * 100);
                this.remainingSeconds = Math.max(0, Math.round(this.estimatedDuration - elapsed));
                if (this.isJobRunning) setTimeout(updateProgress, 500);
            };
            updateProgress();
        },

        handleJobCompletion(status) {
            this.progress = 100;
            this.remainingSeconds = 0;
            this.showCompletionStatus = true;
            this.completionResult = (status === 'completed') ? (this.jobResult || 'success') : 'error';
            this.completionTimeout = setTimeout(() => { this.resetState(); }, 5000);
        },

        // === ERP getters and methods ===
        get erpIsJobRunning() {
            return this.activeErpJobStatus === 'pending' || this.activeErpJobStatus === 'running';
        },

        get erpStatusText() {
            if (this.activeErpJobType === 'sync') return 'Synchronizowanie do ERP...';
            if (this.activeErpJobType === 'pull') return 'Pobieranie danych z ERP...';
            return 'Przetwarzanie ERP...';
        },

        resetErpState() {
            this.erpShowCompletionStatus = false;
            this.erpCompletionResult = null;
            this.erpProgress = 0;
            this.erpRemainingSeconds = 0;
            if (this.erpCompletionTimeout) { clearTimeout(this.erpCompletionTimeout); this.erpCompletionTimeout = null; }
        },

        startErpProgressTracking() {
            this.erpShowCompletionStatus = false;
            this.erpCompletionResult = null;
            if (this.erpCompletionTimeout) { clearTimeout(this.erpCompletionTimeout); this.erpCompletionTimeout = null; }
            const startTime = this.erpJobCreatedAt ? new Date(this.erpJobCreatedAt).getTime() : Date.now();
            const updateProgress = () => {
                if (!this.erpIsJobRunning) return;
                const elapsed = (Date.now() - startTime) / 1000;
                this.erpProgress = Math.min(95, (elapsed / this.erpEstimatedDuration) * 100);
                this.erpRemainingSeconds = Math.max(0, Math.round(this.erpEstimatedDuration - elapsed));
                if (this.erpIsJobRunning) setTimeout(updateProgress, 500);
            };
            updateProgress();
        },

        handleErpJobCompletion(status) {
            this.erpProgress = 100;
            this.erpRemainingSeconds = 0;
            this.erpShowCompletionStatus = true;
            this.erpCompletionResult = (status === 'completed') ? (this.erpJobResult || 'success') : 'error';
            this.erpCompletionTimeout = setTimeout(() => { this.resetErpState(); }, 5000);
        }
    }));
});
</script>

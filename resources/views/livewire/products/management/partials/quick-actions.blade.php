{{-- Quick Actions Panel --}}
<div class="enterprise-card p-6">
    <h4 class="text-lg font-bold text-dark-primary mb-6 flex items-center">
        <i class="fas fa-bolt text-mpp-orange mr-2"></i>
        Szybkie akcje
    </h4>
    <div class="space-y-4">
        {{-- Save Button - Smart mode: "Wróć do Listy" when job pending/processing --}}
        @include('livewire.products.management.partials.actions.save-and-close-button')

        {{-- ETAP_13.2: Aktualizuj sklepy (ALL shops export) - NEW --}}
        @if($isEditMode && !empty($exportedShops))
            <button
                type="button"
                wire:click="bulkUpdateShops"
                class="btn-enterprise-secondary w-full py-3"
                x-data="jobCountdown(@entangle('jobCreatedAt'), @entangle('activeJobStatus'), @entangle('jobResult'), @entangle('activeJobType'))"
                :disabled="$wire.activeJobStatus === 'pending' || $wire.activeJobStatus === 'processing'"
                :class="{
                    'btn-job-running': ($wire.activeJobStatus === 'pending' || $wire.activeJobStatus === 'processing') && $wire.activeJobType === 'sync',
                    'btn-job-success': $wire.jobResult === 'success' && $wire.activeJobType === 'sync',
                    'btn-job-error': $wire.jobResult === 'error' && $wire.activeJobType === 'sync'
                }"
                :style="($wire.activeJobStatus === 'pending' || $wire.activeJobStatus === 'processing') && $wire.activeJobType === 'sync' ? `--progress-percent: ${progress}%` : ''">

                {{-- Show animation for BOTH pending AND processing (FIX 2025-11-18) --}}
                <template x-if="($wire.activeJobStatus === 'pending' || $wire.activeJobStatus === 'processing') && $wire.activeJobType === 'sync'">
                    <span>
                        <i class="fas fa-spinner fa-spin mr-2"></i>
                        Aktualizowanie... (<span x-text="remainingSeconds"></span>s)
                    </span>
                </template>

                <template x-if="$wire.jobResult === 'success' && $wire.activeJobType === 'sync'">
                    <span>
                        <i class="fas fa-check mr-2"></i>
                        SUKCES
                    </span>
                </template>

                <template x-if="$wire.jobResult === 'error' && $wire.activeJobType === 'sync'">
                    <span>
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        BŁĄD
                    </span>
                </template>

                {{-- Default state: Show when NO active job AND (no result OR result cleared) --}}
                <template x-if="(!$wire.activeJobStatus || $wire.activeJobStatus === 'completed' || $wire.activeJobStatus === 'failed') && !$wire.jobResult">
                    <span>
                        <i class="fas fa-cloud-upload-alt mr-2"></i>
                        Aktualizuj sklepy
                    </span>
                </template>
            </button>
        @endif

        {{-- ETAP_13.2: Wczytaj ze sklepów (ALL shops import) - NEW --}}
        @if($isEditMode && !empty($exportedShops))
            <button
                type="button"
                wire:click="bulkPullFromShops"
                class="btn-enterprise-secondary w-full py-3"
                x-data="jobCountdown(@entangle('jobCreatedAt'), @entangle('activeJobStatus'), @entangle('jobResult'), @entangle('activeJobType'))"
                :disabled="$wire.activeJobStatus === 'pending' || $wire.activeJobStatus === 'processing'"
                :class="{
                    'btn-job-running': ($wire.activeJobStatus === 'pending' || $wire.activeJobStatus === 'processing') && $wire.activeJobType === 'pull',
                    'btn-job-success': $wire.jobResult === 'success' && $wire.activeJobType === 'pull',
                    'btn-job-error': $wire.jobResult === 'error' && $wire.activeJobType === 'pull'
                }"
                :style="($wire.activeJobStatus === 'pending' || $wire.activeJobStatus === 'processing') && $wire.activeJobType === 'pull' ? `--progress-percent: ${progress}%` : ''">

                {{-- Show animation for BOTH pending AND processing (FIX 2025-11-18) --}}
                <template x-if="($wire.activeJobStatus === 'pending' || $wire.activeJobStatus === 'processing') && $wire.activeJobType === 'pull'">
                    <span>
                        <i class="fas fa-spinner fa-spin mr-2"></i>
                        Wczytywanie... (<span x-text="remainingSeconds"></span>s)
                    </span>
                </template>

                <template x-if="$wire.jobResult === 'success' && $wire.activeJobType === 'pull'">
                    <span>
                        <i class="fas fa-check mr-2"></i>
                        SUKCES
                    </span>
                </template>

                <template x-if="$wire.jobResult === 'error' && $wire.activeJobType === 'pull'">
                    <span>
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        BŁĄD
                    </span>
                </template>

                {{-- Default state: Show when NO active job AND (no result OR result cleared) --}}
                <template x-if="(!$wire.activeJobStatus || $wire.activeJobStatus === 'completed' || $wire.activeJobStatus === 'failed') && !$wire.jobResult">
                    <span>
                        <i class="fas fa-cloud-download-alt mr-2"></i>
                        Wczytaj ze sklepów
                    </span>
                </template>
            </button>
        @endif

        {{-- Cancel Button --}}
        @include('livewire.products.management.partials.actions.cancel-link')
    </div>
</div>

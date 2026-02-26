<div x-data="{
    scanPolling: false,
    init() {
        if (@js($scanPhase) !== 'idle') {
            this.startPolling();
        }

        Livewire.on('scan-started', () => this.startPolling());
        Livewire.on('scan-completed', () => this.stopPolling());
    },
    async startPolling() {
        this.scanPolling = true;
        while (this.scanPolling && $wire.scanPhase === 'scanning') {
            await $wire.processNextChunk();
            await new Promise(r => setTimeout(r, 200));
        }
    },
    stopPolling() {
        this.scanPolling = false;
    }
}">
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-white">Macierz Produktow</h1>
        <p class="mt-1 text-sm text-gray-400">Przegladaj status produktow we wszystkich zrodlach</p>
    </div>

    {{-- Tabs --}}
    <div class="flex space-x-1 mb-6 border-b border-gray-700">
        <button wire:click="setTab('matrix')"
                class="px-4 py-2 text-sm font-medium rounded-t-lg transition-colors {{ $activeTab === 'matrix' ? 'matrix-tab-active' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
            Macierz
        </button>
        <button wire:click="setTab('history')"
                class="px-4 py-2 text-sm font-medium rounded-t-lg transition-colors {{ $activeTab === 'history' ? 'matrix-tab-active' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
            Historia
        </button>
    </div>

    {{-- Content --}}
    @if($activeTab === 'matrix')
        {{-- Auto-refresh gdy sa komorki pending_sync (po dispatch joba) --}}
        @if($this->hasPendingSyncCells())
            <div wire:poll.5s="pollPendingSync"></div>
        @endif

        @include('livewire.admin.scan.matrix.summary-bar')
        @include('livewire.admin.scan.matrix.brand-suggestions')
        @include('livewire.admin.scan.matrix.toolbar')
        @include('livewire.admin.scan.matrix.bulk-actions-bar')
        @include('livewire.admin.scan.matrix.scan-progress')
        @include('livewire.admin.scan.matrix.table')
        @include('livewire.admin.scan.matrix.cell-popup')
    @else
        @include('livewire.admin.scan.partials.history-list')
    @endif
</div>

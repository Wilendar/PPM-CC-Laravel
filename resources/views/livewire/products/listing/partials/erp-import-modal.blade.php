{{--
    ERP Import Modal
    FAZA 10: Import z ERP w ProductList

    Umozliwia import produktow z podlaczonych systemow ERP
    (BaseLinker, Subiekt GT, Microsoft Dynamics).

    FIXED: wire:model.live dla textarea
    ADDED: Tryby wyszukiwania (ID, SKU, Nazwa) jak w PrestaShop import
--}}

@if($showERPImportModal)
<div class="fixed inset-0 z-50 overflow-y-auto"
     aria-labelledby="erp-import-modal-title"
     role="dialog"
     aria-modal="true"
     x-data="{ show: @entangle('showERPImportModal') }"
     x-show="show"
     x-cloak>

    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/60 transition-opacity"
         x-show="show"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         wire:click="closeERPImportModal"></div>

    {{-- Modal Panel --}}
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative transform overflow-hidden rounded-xl bg-card border border-primary shadow-xl transition-all w-full max-w-lg"
             x-show="show"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @click.away="$wire.closeERPImportModal()">

            {{-- Header --}}
            <div class="flex items-center justify-between p-4 border-b border-primary">
                <div class="flex items-center gap-3">
                    <div class="icon-chip">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                    </div>
                    <h3 id="erp-import-modal-title" class="text-lg font-semibold text-primary">
                        Import produktow z ERP
                    </h3>
                </div>
                <button wire:click="closeERPImportModal"
                        class="text-muted hover:text-primary transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-4 space-y-4">

                {{-- Step 1: Connection Selection --}}
                <div>
                    <label class="block text-sm font-medium text-secondary mb-2">
                        1. Wybierz polaczenie ERP
                    </label>

                    @if($this->availableERPConnections->isEmpty())
                        <div class="enterprise-card-warning p-3 rounded-lg">
                            <p class="text-sm text-yellow-400">
                                Brak aktywnych polaczen ERP. Skonfiguruj polaczenie w
                                <a href="{{ route('admin.integrations.erp') }}" class="underline hover:text-yellow-300">
                                    Panelu integracji
                                </a>.
                            </p>
                        </div>
                    @else
                        <select wire:model.live="selectedERPConnectionId"
                                class="form-input w-full rounded-lg">
                            <option value="">-- Wybierz polaczenie --</option>
                            @foreach($this->availableERPConnections as $conn)
                                <option value="{{ $conn->id }}">
                                    {{ $conn->instance_name }}
                                    ({{ ucfirst($conn->erp_type) }})
                                    @if($conn->last_sync_at)
                                        - ostatnia sync: {{ $conn->last_sync_at->diffForHumans() }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                {{-- Step 2: Import Mode (only if connection selected) --}}
                @if($selectedERPConnectionId)
                    <div>
                        <label class="block text-sm font-medium text-secondary mb-2">
                            2. Tryb importu
                        </label>

                        <div class="flex gap-2">
                            <button wire:click="$set('erpImportMode', 'all')"
                                    class="flex-1 px-4 py-2 text-sm font-medium rounded-lg transition-all duration-300
                                           {{ $erpImportMode === 'all'
                                              ? 'bg-orange-500 text-white shadow-soft'
                                              : 'bg-card-hover text-secondary hover:bg-card border border-primary' }}">
                                Wszystkie
                            </button>
                            <button wire:click="$set('erpImportMode', 'individual')"
                                    class="flex-1 px-4 py-2 text-sm font-medium rounded-lg transition-all duration-300
                                           {{ $erpImportMode === 'individual'
                                              ? 'bg-orange-500 text-white shadow-soft'
                                              : 'bg-card-hover text-secondary hover:bg-card border border-primary' }}">
                                Wybrane
                            </button>
                        </div>
                    </div>

                    {{-- Mode: All Products --}}
                    @if($erpImportMode === 'all')
                        <div class="enterprise-card p-4 rounded-lg">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div class="text-sm text-secondary">
                                    <p class="font-medium text-primary mb-1">Import wszystkich produktow</p>
                                    <p>System pobierze wszystkie produkty z wybranego ERP i utworzy/zaktualizuje je w PPM.</p>
                                    <p class="mt-2 text-muted">Produkty sa dopasowywane po SKU (SKU-First Architecture).</p>
                                </div>
                            </div>
                        </div>

                        <button wire:click="importAllFromERP"
                                wire:loading.attr="disabled"
                                wire:target="importAllFromERP"
                                class="btn-enterprise-primary w-full py-3"
                                {{ $erpImportLoading ? 'disabled' : '' }}>
                            <span wire:loading.remove wire:target="importAllFromERP">
                                Rozpocznij import wszystkich
                            </span>
                            <span wire:loading wire:target="importAllFromERP" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Uruchamianie...
                            </span>
                        </button>
                    @endif

                    {{-- Mode: Individual Products --}}
                    @if($erpImportMode === 'individual')
                        {{-- Search Type Selection --}}
                        <div>
                            <label class="block text-sm font-medium text-secondary mb-2">
                                3. Typ wyszukiwania
                            </label>
                            <div class="flex gap-2">
                                <button wire:click="$set('erpSearchType', 'id')"
                                        class="flex-1 px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-300
                                               {{ $erpSearchType === 'id'
                                                  ? 'bg-blue-600 text-white'
                                                  : 'bg-card-hover text-secondary hover:bg-card border border-primary' }}">
                                    ID produktu
                                </button>
                                <button wire:click="$set('erpSearchType', 'sku')"
                                        class="flex-1 px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-300
                                               {{ $erpSearchType === 'sku'
                                                  ? 'bg-blue-600 text-white'
                                                  : 'bg-card-hover text-secondary hover:bg-card border border-primary' }}">
                                    SKU
                                </button>
                                <button wire:click="$set('erpSearchType', 'name')"
                                        class="flex-1 px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-300
                                               {{ $erpSearchType === 'name'
                                                  ? 'bg-blue-600 text-white'
                                                  : 'bg-card-hover text-secondary hover:bg-card border border-primary' }}">
                                    Nazwa
                                </button>
                            </div>
                        </div>

                        {{-- Search Input --}}
                        <div>
                            <label class="block text-sm font-medium text-secondary mb-2">
                                4. Podaj
                                @if($erpSearchType === 'id')
                                    ID produktow (oddzielone przecinkami)
                                @elseif($erpSearchType === 'sku')
                                    SKU produktow (oddzielone przecinkami)
                                @else
                                    nazwe produktu (min. 3 znaki)
                                @endif
                            </label>

                            @if($erpSearchType === 'name')
                                {{-- Name search - single input with live search --}}
                                <input type="text"
                                       wire:model.live.debounce.500ms="erpSearchQuery"
                                       class="form-input w-full rounded-lg"
                                       placeholder="Wpisz min. 3 znaki nazwy produktu...">

                                @if(!empty($erpSearchQuery) && strlen($erpSearchQuery) >= 3)
                                    <p class="mt-1 text-xs text-orange-400">
                                        Wyszukiwanie: "{{ $erpSearchQuery }}"
                                    </p>
                                @endif
                            @else
                                {{-- ID or SKU - textarea for multiple values --}}
                                <textarea wire:model.live="erpProductIds"
                                          rows="3"
                                          class="form-input w-full rounded-lg"
                                          placeholder="{{ $erpSearchType === 'id' ? 'Wpisz ID oddzielone przecinkami, np.: 51567472, 51567473' : 'Wpisz SKU oddzielone przecinkami, np.: ABC-001, XYZ-002' }}"></textarea>
                            @endif

                            <p class="mt-1 text-xs text-muted">
                                @if($erpSearchType === 'id')
                                    ID produktow w systemie {{ $this->selectedERPConnection?->erp_type ?? 'ERP' }}.
                                @elseif($erpSearchType === 'sku')
                                    SKU produktow - dopasowanie dokladne.
                                @else
                                    Wyszukiwanie po nazwie produktu w {{ $this->selectedERPConnection?->erp_type ?? 'ERP' }}.
                                @endif
                            </p>
                        </div>

                        {{-- Search Results (for name search) --}}
                        @if($erpSearchType === 'name' && !empty($erpSearchResults))
                            <div class="max-h-48 overflow-y-auto border border-primary rounded-lg">
                                @foreach($erpSearchResults as $result)
                                    <label class="flex items-center gap-3 p-3 hover:bg-card-hover cursor-pointer border-b border-primary last:border-b-0">
                                        <input type="checkbox"
                                               wire:model.live="selectedERPProducts"
                                               value="{{ $result['id'] }}"
                                               class="checkbox-enterprise">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-primary truncate">
                                                {{ $result['name'] }}
                                            </p>
                                            <p class="text-xs text-muted">
                                                ID: {{ $result['id'] }} | SKU: {{ $result['sku'] ?? 'brak' }}
                                            </p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            <p class="text-xs text-muted">
                                Zaznaczono: {{ count($selectedERPProducts) }} produktow
                            </p>
                        @endif

                        {{-- Import Button --}}
                        <button wire:click="importSelectedFromERP"
                                wire:loading.attr="disabled"
                                wire:target="importSelectedFromERP"
                                class="btn-enterprise-primary w-full py-3"
                                @if($erpImportLoading || ($erpSearchType !== 'name' && empty(trim($erpProductIds))) || ($erpSearchType === 'name' && empty($selectedERPProducts))) disabled @endif>
                            <span wire:loading.remove wire:target="importSelectedFromERP">
                                @if($erpSearchType === 'name')
                                    Importuj zaznaczone ({{ count($selectedERPProducts) }})
                                @else
                                    Importuj wybrane produkty
                                @endif
                            </span>
                            <span wire:loading wire:target="importSelectedFromERP" class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Uruchamianie...
                            </span>
                        </button>
                    @endif
                @endif
            </div>

            {{-- Footer --}}
            <div class="flex justify-end gap-3 p-4 border-t border-primary bg-card-hover/50">
                <button wire:click="closeERPImportModal"
                        class="btn-enterprise-secondary px-4 py-2">
                    Anuluj
                </button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ETAP_06 FAZA 5.7: ImageUploadModal - Zarzadzanie zdjeciami dla pending products --}}
<div>
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="image-modal-title" role="dialog" aria-modal="true">
        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-black/70 transition-opacity" wire:click="closeModal"></div>

        {{-- Modal container --}}
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-4xl bg-gray-800 rounded-xl shadow-2xl border border-gray-700"
                 @keydown.escape.window="$wire.closeModal()">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-700">
                    <div>
                        <h3 id="image-modal-title" class="text-lg font-semibold text-white">
                            Zdjecia produktu
                        </h3>
                        @if($pendingProduct)
                        <p class="text-sm text-gray-400 mt-1">
                            {{ $pendingProduct->sku }} - {{ $pendingProduct->name ?? '(brak nazwy)' }}
                        </p>
                        @endif
                    </div>
                    <button wire:click="closeModal"
                            class="text-gray-400 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="p-6 max-h-[70vh] overflow-y-auto">

                    {{-- Upload options --}}
                    @include('livewire.products.import.modals.partials._image-upload-sources')

                    {{-- Variant assignment toggle --}}
                    @if(count($variants) > 0)
                    <div class="mb-4 p-3 bg-purple-900/30 border border-purple-700/50 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                <span class="text-sm text-purple-300">
                                    Produkt ma <strong class="text-purple-200">{{ count($variants) }}</strong> wariantow
                                </span>
                            </div>
                            <button type="button"
                                    wire:click="toggleVariantAssignment"
                                    class="px-3 py-1.5 text-xs bg-purple-900/40 hover:bg-purple-900/60 text-purple-300 border border-purple-700/50 rounded-lg transition-colors">
                                {{ $showVariantAssignment ? 'Ukryj przypisania' : 'Przypisz do wariantow' }}
                            </button>
                        </div>

                        @if($showVariantAssignment)
                        <div class="mt-3 pt-3 border-t border-purple-700/50">
                            <p class="text-xs text-gray-400 mb-2">
                                Wybierz wariant dla kazdego zdjecia. Kliknij gwiazdke aby ustawic okladke wariantu.
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-2 py-1 bg-gray-700 text-gray-300 text-xs rounded">
                                    Produkt glowny
                                </span>
                                @foreach($variants as $variant)
                                <span class="px-2 py-1 bg-purple-700/50 text-purple-200 text-xs rounded"
                                      title="{{ $variant['sku_suffix'] ?? '' }}">
                                    {{ $this->getVariantDisplayName($variant) }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif

                    {{-- Image gallery --}}
                    <div class="space-y-3">
                        {{-- Gallery header with view toggle --}}
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <h4 class="text-sm font-medium text-gray-300 uppercase tracking-wider">
                                    Galeria
                                    @if(count($images) > 0)
                                    <span class="ml-2 px-2 py-0.5 bg-green-900/50 text-green-300 border border-green-700 text-xs rounded-full">
                                        {{ count($images) }}
                                    </span>
                                    @endif
                                </h4>

                                {{-- View mode toggle (only when variants exist) --}}
                                @if(count($variants) > 0 && count($images) > 0)
                                <div class="import-view-toggle">
                                    <button type="button"
                                            wire:click="setViewMode('grid')"
                                            class="import-view-toggle-btn {{ $viewMode === 'grid' ? 'import-view-toggle-btn-active' : '' }}">
                                        Siatka
                                    </button>
                                    <button type="button"
                                            wire:click="setViewMode('grouped')"
                                            class="import-view-toggle-btn {{ $viewMode === 'grouped' ? 'import-view-toggle-btn-active' : '' }}">
                                        Grupy
                                    </button>
                                </div>
                                @endif
                            </div>

                            @if(count($images) > 0)
                            <button type="button"
                                    wire:click="clearImages"
                                    wire:confirm="Czy na pewno usunac wszystkie zdjecia?"
                                    class="text-xs text-red-400 hover:text-red-300">
                                Wyczysc wszystkie
                            </button>
                            @endif
                        </div>

                        {{-- Variant filter buttons (grid mode only, when variants exist) --}}
                        @if($viewMode === 'grid' && count($variants) > 0 && count($images) > 0)
                        @php $imageCounts = $this->variantImageCounts; @endphp
                        <div class="import-variant-filter-bar">
                            <button type="button"
                                    wire:click="setVariantFilter('')"
                                    class="import-variant-filter-btn {{ $variantFilter === null ? 'import-variant-filter-btn-active' : '' }}">
                                Wszystkie ({{ $imageCounts['_all'] ?? 0 }})
                            </button>
                            <button type="button"
                                    wire:click="setVariantFilter('_main')"
                                    class="import-variant-filter-btn {{ $variantFilter === '_main' ? 'import-variant-filter-btn-active' : '' }}">
                                Produkt glowny ({{ $imageCounts['_main'] ?? 0 }})
                            </button>
                            @foreach($variants as $variant)
                            @php $vSku = $variant['sku_suffix'] ?? ''; @endphp
                            @if($vSku !== '')
                            <button type="button"
                                    wire:click="setVariantFilter('{{ $vSku }}')"
                                    class="import-variant-filter-btn {{ $variantFilter === $vSku ? 'import-variant-filter-btn-active' : '' }}">
                                {{ $this->getVariantDisplayName($variant) }} ({{ $imageCounts[$vSku] ?? 0 }})
                            </button>
                            @endif
                            @endforeach
                        </div>
                        @endif

                        {{-- Batch toolbar (sticky) --}}
                        @include('livewire.products.import.modals.partials.batch-toolbar')

                        {{-- Gallery content --}}
                        @if(count($images) > 0)
                            @if($viewMode === 'grouped' && count($variants) > 0)
                                {{-- Grouped view --}}
                                @include('livewire.products.import.modals.partials.image-gallery-grouped')
                            @else
                                {{-- Grid view (with optional filter) --}}
                                @php $displayImages = $this->filteredImages; @endphp
                                @if(count($displayImages) > 0)
                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                    @foreach($displayImages as $index => $image)
                                        @php
                                            $isGlobalCover = $image['is_cover'] ?? false;
                                            $variantSku = $image['variant_sku'] ?? null;
                                            $isVariantCover = $variantSku && isset($variantCovers[$variantSku]) && $variantCovers[$variantSku] === $index;
                                        @endphp
                                        @include('livewire.products.import.modals.partials.image-card', [
                                            'image' => $image,
                                            'index' => $index,
                                            'isGlobalCover' => $isGlobalCover,
                                            'isVariantCover' => $isVariantCover,
                                            'showVariantAssignment' => $showVariantAssignment,
                                            'variants' => $variants,
                                            'variantCovers' => $variantCovers,
                                            'images' => $images,
                                            'selectedImages' => $selectedImages,
                                        ])
                                    @endforeach
                                </div>
                                @else
                                <div class="p-6 bg-gray-700/30 rounded-lg text-center">
                                    <p class="text-gray-400 text-sm">Brak zdjec dla wybranego filtru</p>
                                    <button type="button"
                                            wire:click="setVariantFilter('')"
                                            class="mt-2 text-xs text-blue-400 hover:text-blue-300">
                                        Pokaz wszystkie
                                    </button>
                                </div>
                                @endif
                            @endif
                        @else
                        <div class="p-8 bg-gray-700/30 rounded-lg text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-gray-400 text-sm">Brak zdjec</p>
                            <p class="text-gray-500 text-xs mt-1">Wgraj zdjecia, pobierz z URL lub skopiuj z innego produktu</p>
                        </div>
                        @endif
                    </div>

                    {{-- Variant image preview section (grid mode only) --}}
                    @if($viewMode === 'grid' && $showVariantAssignment && count($variants) > 0 && count($images) > 0)
                        @include('livewire.products.import.modals.partials._variant-preview')
                    @endif
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-between px-6 py-4 border-t border-gray-700 bg-gray-800/50">
                    <div class="flex items-center gap-4">
                        <div class="text-sm text-gray-400">
                            Zdjec: <span class="text-green-400 font-medium">{{ count($images) }}</span>
                        </div>

                        @if($this->isSkipped)
                        <div class="flex items-center gap-2 px-3 py-1.5 bg-red-900/30 border border-red-600/50 rounded-lg">
                            <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            <span class="text-sm text-red-400">Oznaczono jako "Publikuj bez zdjec"</span>
                            <button type="button"
                                    wire:click="clearSkipImages"
                                    class="ml-1 text-red-400 hover:text-red-300 underline text-xs">
                                Cofnij
                            </button>
                        </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-3">
                        @if(!$this->isSkipped)
                        <button type="button"
                                wire:click="setSkipImages"
                                wire:confirm="Czy na pewno oznaczyc jako 'Publikuj bez zdjec'? Produkt zostanie oznaczony jako kompletny bez zdjec."
                                class="px-4 py-2 bg-red-600/30 hover:bg-red-600/50 text-red-400 border border-red-600/50
                                       rounded-lg transition-colors text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Publikuj bez zdjec
                        </button>
                        @endif

                        <button type="button"
                                wire:click="closeModal"
                                class="btn-enterprise-secondary">
                            Anuluj
                        </button>

                        <button type="button"
                                wire:click="saveImages"
                                @disabled($isProcessing)
                                class="btn-enterprise-success disabled:opacity-50 disabled:cursor-not-allowed">
                            @if($isProcessing)
                            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white inline-block" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Zapisywanie...
                            @else
                            Zapisz zdjecia
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

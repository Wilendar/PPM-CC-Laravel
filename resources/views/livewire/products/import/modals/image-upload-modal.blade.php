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
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        {{-- File upload with drag & drop --}}
                        <div class="p-4 bg-gray-700/30 rounded-lg"
                             x-data="{ isDragging: false }"
                             @dragover.prevent="isDragging = true"
                             @dragleave.prevent="isDragging = false"
                             @drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))">
                            <h4 class="text-sm font-medium text-gray-300 mb-3">
                                Wgraj z dysku
                            </h4>
                            <label class="flex flex-col items-center justify-center p-4 border-2 border-dashed rounded-lg cursor-pointer transition-colors"
                                   :class="isDragging ? 'border-green-500 bg-green-500/10' : 'border-gray-600 hover:border-gray-500'">
                                <svg class="w-8 h-8 mb-2 transition-colors" :class="isDragging ? 'text-green-400' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <span class="text-sm transition-colors" :class="isDragging ? 'text-green-400' : 'text-gray-400'">
                                    <span x-show="!isDragging">Kliknij lub upusc pliki</span>
                                    <span x-show="isDragging" x-cloak>Upusc pliki tutaj!</span>
                                </span>
                                <span class="text-xs text-gray-500 mt-1">JPG, PNG, GIF, WEBP do 10MB</span>
                                <input type="file"
                                       x-ref="fileInput"
                                       wire:model="uploadedFiles"
                                       multiple
                                       accept="image/*"
                                       class="hidden">
                            </label>
                            @if($isUploading)
                            <div class="mt-2 text-center text-sm text-gray-400">
                                <svg class="animate-spin inline-block w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Wgrywanie...
                            </div>
                            @endif
                        </div>

                        {{-- URL import --}}
                        <div class="p-4 bg-gray-700/30 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-300 mb-3">
                                Pobierz z URL
                            </h4>
                            <div class="space-y-2">
                                <input type="text"
                                       wire:model.live="imageUrl"
                                       placeholder="https://..."
                                       class="form-input-dark-sm w-full"
                                       wire:keydown.enter="importFromUrl">
                                <button type="button"
                                        wire:click="importFromUrl"
                                        @disabled(empty($imageUrl) || $isUploading)
                                        class="w-full px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg
                                               text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                    Pobierz
                                </button>
                            </div>
                        </div>

                        {{-- Copy from product with autocomplete --}}
                        <div class="p-4 bg-gray-700/30 rounded-lg"
                             x-data="{ focused: false }"
                             @click.away="focused = false; $wire.hideSkuSuggestions()">
                            <h4 class="text-sm font-medium text-gray-300 mb-3">
                                Kopiuj z produktu
                            </h4>
                            <div class="space-y-2">
                                <div class="relative">
                                    <input type="text"
                                           wire:model.live.debounce.300ms="copyFromSku"
                                           placeholder="Wpisz SKU..."
                                           class="form-input-dark-sm w-full"
                                           @focus="focused = true"
                                           wire:keydown.enter="copyFromProduct"
                                           wire:keydown.escape="$wire.hideSkuSuggestions()"
                                           autocomplete="off">

                                    @if($showSkuSuggestions && count($skuSuggestions) > 0)
                                    <div class="absolute z-50 w-full mt-1 bg-gray-800 border border-gray-600 rounded-lg shadow-xl max-h-64 overflow-y-auto">
                                        @foreach($skuSuggestions as $suggestion)
                                        <button type="button"
                                                wire:click="selectSkuSuggestion('{{ $suggestion['sku'] }}')"
                                                class="w-full px-3 py-2 text-left hover:bg-gray-700 transition-colors flex items-center gap-2
                                                       {{ $loop->first ? 'rounded-t-lg' : '' }}
                                                       {{ $loop->last ? 'rounded-b-lg' : '' }}
                                                       border-b border-gray-700 last:border-b-0">
                                            <span class="shrink-0 px-1.5 py-0.5 text-xs rounded
                                                         {{ $suggestion['source'] === 'pending' ? 'bg-yellow-600 text-yellow-100' : 'bg-blue-600 text-blue-100' }}">
                                                {{ $suggestion['source'] === 'pending' ? 'Import' : 'Prod' }}
                                            </span>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm text-white font-medium truncate">{{ $suggestion['sku'] }}</div>
                                                <div class="text-xs text-gray-400 truncate">{{ Str::limit($suggestion['name'], 30) }}</div>
                                            </div>
                                            @if($suggestion['has_images'])
                                            <span class="shrink-0 flex items-center gap-1 px-1.5 py-0.5 bg-green-600 text-green-100 text-xs rounded">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                                {{ $suggestion['image_count'] }}
                                            </span>
                                            @else
                                            <span class="shrink-0 px-1.5 py-0.5 bg-gray-600 text-gray-300 text-xs rounded">0 zdjec</span>
                                            @endif
                                        </button>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                                <button type="button"
                                        wire:click="copyFromProduct"
                                        @disabled(empty($copyFromSku) || $isUploading)
                                        class="w-full px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg
                                               text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                    Kopiuj zdjecia
                                </button>
                            </div>
                        </div>
                    </div>

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
                                    class="px-3 py-1.5 text-xs bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">
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
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-medium text-gray-300 uppercase tracking-wider">
                                Galeria
                                @if(count($images) > 0)
                                <span class="ml-2 px-2 py-0.5 bg-green-600 text-white text-xs rounded-full">
                                    {{ count($images) }}
                                </span>
                                @endif
                            </h4>
                            @if(count($images) > 0)
                            <button type="button"
                                    wire:click="clearImages"
                                    wire:confirm="Czy na pewno usunac wszystkie zdjecia?"
                                    class="text-xs text-red-400 hover:text-red-300">
                                Wyczysc wszystkie
                            </button>
                            @endif
                        </div>

                        @if(count($images) > 0)
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            @foreach($images as $index => $image)
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
                                ])
                            @endforeach
                        </div>
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

                    {{-- Variant image preview section --}}
                    @if($showVariantAssignment && count($variants) > 0 && count($images) > 0)
                    <div class="mt-6 space-y-3">
                        <h4 class="text-sm font-medium text-gray-300 uppercase tracking-wider">
                            Podglad wariantow
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($this->variantImageGroups as $groupKey => $groupImages)
                            @if($groupKey === '_main')
                                @continue
                            @endif
                            @php
                                $variantInfo = collect($variants)->firstWhere('sku_suffix', $groupKey);
                                $variantName = $variantInfo ? $this->getVariantDisplayName($variantInfo) : $groupKey;
                                $coverIndex = $variantCovers[$groupKey] ?? null;
                            @endphp
                            <div class="p-3 bg-gray-700/30 rounded-lg border border-purple-700/30">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="px-2 py-0.5 bg-purple-600 text-white text-xs rounded font-medium">
                                        {{ $variantName }}
                                    </span>
                                    <span class="text-xs text-gray-500">{{ $groupKey }}</span>
                                    <span class="text-xs text-gray-500 ml-auto">
                                        {{ count($groupImages) }} {{ count($groupImages) === 1 ? 'zdjecie' : 'zdjec' }}
                                    </span>
                                </div>

                                @if(count($groupImages) > 0)
                                <div class="flex flex-wrap gap-2">
                                    @foreach($groupImages as $item)
                                    @php
                                        $isCover = $coverIndex === $item['index'];
                                    @endphp
                                    <button type="button"
                                            wire:click="setVariantCover({{ $item['index'] }}, '{{ $groupKey }}')"
                                            class="relative w-12 h-12 rounded overflow-hidden
                                                   {{ $isCover ? 'ring-2 ring-amber-500' : 'ring-1 ring-gray-600 hover:ring-amber-400' }}
                                                   transition-all"
                                            title="{{ $isCover ? 'Okladka wariantu' : 'Kliknij aby ustawic jako okladke wariantu' }}">
                                        <img src="{{ Storage::disk('public')->url($item['image']['path']) }}"
                                             alt="{{ $item['image']['filename'] ?? '' }}"
                                             class="w-full h-full object-cover">
                                        @if($isCover)
                                        <div class="absolute inset-0 bg-amber-500/20 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                            </svg>
                                        </div>
                                        @endif
                                    </button>
                                    @endforeach
                                </div>
                                @else
                                <p class="text-xs text-gray-500 italic">Brak przypisanych zdjec</p>
                                @endif
                            </div>
                            @endforeach

                            {{-- Main product images --}}
                            @php $mainImages = $this->variantImageGroups['_main'] ?? []; @endphp
                            @if(count($mainImages) > 0)
                            <div class="p-3 bg-gray-700/30 rounded-lg border border-gray-600/30">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="px-2 py-0.5 bg-gray-600 text-gray-200 text-xs rounded font-medium">
                                        Produkt glowny
                                    </span>
                                    <span class="text-xs text-gray-500 ml-auto">
                                        {{ count($mainImages) }} {{ count($mainImages) === 1 ? 'zdjecie' : 'zdjec' }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($mainImages as $item)
                                    <div class="relative w-12 h-12 rounded overflow-hidden ring-1 ring-gray-600">
                                        <img src="{{ Storage::disk('public')->url($item['image']['path']) }}"
                                             alt="{{ $item['image']['filename'] ?? '' }}"
                                             class="w-full h-full object-cover">
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
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
                                class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition-colors">
                            Anuluj
                        </button>

                        <button type="button"
                                wire:click="saveImages"
                                @disabled($isProcessing)
                                class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors
                                       font-medium disabled:opacity-50 disabled:cursor-not-allowed">
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

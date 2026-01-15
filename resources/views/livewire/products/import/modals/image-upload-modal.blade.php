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
                                {{-- Input with autocomplete --}}
                                <div class="relative">
                                    <input type="text"
                                           wire:model.live.debounce.300ms="copyFromSku"
                                           placeholder="Wpisz SKU..."
                                           class="form-input-dark-sm w-full"
                                           @focus="focused = true"
                                           wire:keydown.enter="copyFromProduct"
                                           wire:keydown.escape="$wire.hideSkuSuggestions()"
                                           autocomplete="off">

                                    {{-- SKU Suggestions dropdown --}}
                                    @if($showSkuSuggestions && count($skuSuggestions) > 0)
                                    <div class="absolute z-50 w-full mt-1 bg-gray-800 border border-gray-600 rounded-lg shadow-xl max-h-64 overflow-y-auto">
                                        @foreach($skuSuggestions as $suggestion)
                                        <button type="button"
                                                wire:click="selectSkuSuggestion('{{ $suggestion['sku'] }}')"
                                                class="w-full px-3 py-2 text-left hover:bg-gray-700 transition-colors flex items-center gap-2
                                                       {{ $loop->first ? 'rounded-t-lg' : '' }}
                                                       {{ $loop->last ? 'rounded-b-lg' : '' }}
                                                       border-b border-gray-700 last:border-b-0">
                                            {{-- Source badge --}}
                                            <span class="shrink-0 px-1.5 py-0.5 text-xs rounded
                                                         {{ $suggestion['source'] === 'pending' ? 'bg-yellow-600 text-yellow-100' : 'bg-blue-600 text-blue-100' }}">
                                                {{ $suggestion['source'] === 'pending' ? 'Import' : 'Prod' }}
                                            </span>

                                            {{-- SKU and name --}}
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm text-white font-medium truncate">
                                                    {{ $suggestion['sku'] }}
                                                </div>
                                                <div class="text-xs text-gray-400 truncate">
                                                    {{ Str::limit($suggestion['name'], 30) }}
                                                </div>
                                            </div>

                                            {{-- Image count badge --}}
                                            @if($suggestion['has_images'])
                                            <span class="shrink-0 flex items-center gap-1 px-1.5 py-0.5 bg-green-600 text-green-100 text-xs rounded">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                                {{ $suggestion['image_count'] }}
                                            </span>
                                            @else
                                            <span class="shrink-0 px-1.5 py-0.5 bg-gray-600 text-gray-300 text-xs rounded">
                                                0 zdjec
                                            </span>
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

                    {{-- Variant assignment toggle (only if has variants) --}}
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
                                Wybierz wariant dla kazdego zdjecia. Zdjecia bez przypisania beda uzywane dla produktu glownego.
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
                            <div class="relative group bg-gray-700/50 rounded-lg overflow-hidden
                                        {{ ($image['is_cover'] ?? false) ? 'ring-2 ring-green-500' : '' }}">
                                {{-- Image preview --}}
                                <div class="aspect-square relative">
                                    <img src="{{ Storage::disk('public')->url($image['path']) }}"
                                         alt="{{ $image['filename'] ?? 'Image' }}"
                                         class="w-full h-full object-cover">

                                    {{-- Cover badge --}}
                                    @if($image['is_cover'] ?? false)
                                    <div class="absolute top-2 left-2 px-2 py-1 bg-green-600 text-white text-xs rounded">
                                        Okladka
                                    </div>
                                    @endif

                                    {{-- Position badge --}}
                                    <div class="absolute top-2 right-2 w-6 h-6 bg-gray-900/80 text-white text-xs
                                                rounded-full flex items-center justify-center">
                                        {{ $index + 1 }}
                                    </div>

                                    {{-- Hover overlay --}}
                                    <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100
                                                transition-opacity flex items-center justify-center gap-2">
                                        {{-- Set as cover --}}
                                        @if(!($image['is_cover'] ?? false))
                                        <button type="button"
                                                wire:click="setCover({{ $index }})"
                                                class="p-2 bg-green-600 hover:bg-green-700 rounded-lg text-white"
                                                title="Ustaw jako okladke">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                        @endif

                                        {{-- Move up --}}
                                        @if($index > 0)
                                        <button type="button"
                                                wire:click="moveUp({{ $index }})"
                                                class="p-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-white"
                                                title="Przesun w gore">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M5 15l7-7 7 7"/>
                                            </svg>
                                        </button>
                                        @endif

                                        {{-- Move down --}}
                                        @if($index < count($images) - 1)
                                        <button type="button"
                                                wire:click="moveDown({{ $index }})"
                                                class="p-2 bg-blue-600 hover:bg-blue-700 rounded-lg text-white"
                                                title="Przesun w dol">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                        @endif

                                        {{-- Remove --}}
                                        <button type="button"
                                                wire:click="removeImage({{ $index }})"
                                                class="p-2 bg-red-600 hover:bg-red-700 rounded-lg text-white"
                                                title="Usun">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Filename --}}
                                <div class="p-2 text-center">
                                    <p class="text-xs text-gray-400 truncate" title="{{ $image['filename'] ?? '' }}">
                                        {{ $image['filename'] ?? 'image.jpg' }}
                                    </p>
                                    @if(!empty($image['size']))
                                    <p class="text-xs text-gray-500">
                                        {{ number_format($image['size'] / 1024, 1) }} KB
                                    </p>
                                    @endif
                                </div>

                                {{-- Variant assignment dropdown (when showVariantAssignment is enabled) --}}
                                @if($showVariantAssignment && count($variants) > 0)
                                <div class="px-2 pb-2">
                                    <select wire:change="assignToVariant({{ $index }}, $event.target.value)"
                                            class="w-full text-xs bg-gray-800 border border-gray-600 rounded px-2 py-1.5
                                                   text-gray-300 focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                                        <option value="" {{ empty($image['variant_sku']) ? 'selected' : '' }}>
                                            Produkt glowny
                                        </option>
                                        @foreach($variants as $variant)
                                        <option value="{{ $variant['sku_suffix'] ?? '' }}"
                                                {{ ($image['variant_sku'] ?? '') === ($variant['sku_suffix'] ?? '') ? 'selected' : '' }}>
                                            {{ $this->getVariantDisplayName($variant) }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @if(!empty($image['variant_sku']))
                                    <div class="mt-1 text-center">
                                        <span class="inline-block px-1.5 py-0.5 bg-purple-600 text-white text-xs rounded">
                                            {{ $image['variant_sku'] }}
                                        </span>
                                    </div>
                                    @endif
                                </div>
                                @endif
                            </div>
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
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-between px-6 py-4 border-t border-gray-700 bg-gray-800/50">
                    <div class="flex items-center gap-4">
                        <div class="text-sm text-gray-400">
                            Zdjec: <span class="text-green-400 font-medium">{{ count($images) }}</span>
                        </div>

                        {{-- Skip images info badge --}}
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
                        {{-- Publikuj bez zdjec button --}}
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

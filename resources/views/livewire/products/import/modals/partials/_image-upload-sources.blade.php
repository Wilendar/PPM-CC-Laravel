{{-- Upload sources: File upload, URL import, Copy from product --}}
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
                    class="w-full px-3 py-2 bg-blue-900/40 hover:bg-blue-900/60 text-blue-300 border border-blue-600/50 rounded-lg
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
                                     {{ $suggestion['source'] === 'pending' ? 'bg-yellow-900/50 text-yellow-300 border border-yellow-700/50' : 'bg-blue-900/50 text-blue-300 border border-blue-700/50' }}">
                            {{ $suggestion['source'] === 'pending' ? 'Import' : 'Prod' }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm text-white font-medium truncate">{{ $suggestion['sku'] }}</div>
                            <div class="text-xs text-gray-400 truncate">{{ Str::limit($suggestion['name'], 30) }}</div>
                        </div>
                        @if($suggestion['has_images'])
                        <span class="shrink-0 flex items-center gap-1 px-1.5 py-0.5 bg-green-900/50 text-green-300 border border-green-700/50 text-xs rounded">
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
                    class="w-full px-3 py-2 bg-purple-900/40 hover:bg-purple-900/60 text-purple-300 border border-purple-600/50 rounded-lg
                           text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                Kopiuj zdjecia
            </button>
        </div>
    </div>
</div>

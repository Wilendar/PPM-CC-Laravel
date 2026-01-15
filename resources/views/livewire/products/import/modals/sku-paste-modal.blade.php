{{--
    SKUPasteModal - Modal wklejania listy SKU
    ETAP_06 FAZA 3: Import SKU (wklejanie listy)

    FEATURES:
    - Single column mode (default - SKU + Name w jednej linii)
    - Two column mode (NEW - oddzielne textarea dla SKU i nazw)
    - Count mismatch warning
    - Live preview z rozszerzonymi kolumnami
--}}
<div class="modal-backdrop-enterprise" x-data="{ open: true }" x-show="open" x-cloak>
    <div class="modal-enterprise modal-enterprise-lg" @click.away="$wire.close()">
        {{-- Header --}}
        <div class="modal-header-enterprise">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-[var(--mpp-primary)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Wklej liste SKU
            </h2>
            <button wire:click="close" class="text-gray-400 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="modal-body-enterprise">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Left Column: Input --}}
                <div class="space-y-4">
                    {{-- Import Mode Radio --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Tryb importu</label>
                        <div class="flex gap-4">
                            @foreach($importModes as $mode => $label)
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio"
                                           wire:model.live="importMode"
                                           value="{{ $mode }}"
                                           class="form-radio-enterprise">
                                    <span class="ml-2 text-sm text-gray-300">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- View Mode Toggle (tylko dla sku_name) --}}
                    @if($importMode === 'sku_name')
                        <div class="mb-4 p-3 bg-gray-800/50 rounded-lg border border-gray-700/50">
                            <label class="block text-sm font-medium text-gray-300 mb-3">Widok wklejania</label>
                            <div class="flex gap-4">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio"
                                           wire:model.live="viewMode"
                                           value="single_column"
                                           class="form-radio-enterprise">
                                    <span class="ml-2 text-sm text-gray-300">Jedna kolumna</span>
                                </label>
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="radio"
                                           wire:model.live="viewMode"
                                           value="two_columns"
                                           class="form-radio-enterprise">
                                    <span class="ml-2 text-sm text-gray-300">Dwie kolumny</span>
                                </label>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                <span class="inline-block w-2 h-2 bg-blue-500 rounded-full mr-1"></span>
                                Dwie kolumny: oddzielne pola dla SKU i nazw
                            </p>
                        </div>
                    @endif

                    {{-- Separator Dropdown (tylko dla single_column w trybie sku_name) --}}
                    @if($importMode === 'sku_name' && (!isset($viewMode) || $viewMode === 'single_column'))
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-300 mb-2">Separator kolumn</label>
                            <select wire:model.live="separator" class="form-select-dark w-full">
                                @foreach($separators as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Input Area - Single Column Mode --}}
                    @if($importMode === 'sku_only' || !isset($viewMode) || $viewMode === 'single_column')
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                Wklej dane
                                <span class="text-gray-500 font-normal">
                                    ({{ $importMode === 'sku_only' ? 'jeden SKU na linie' : 'SKU + nazwa na linie' }})
                                </span>
                            </label>
                            <textarea
                                wire:model.live.debounce.500ms="rawInput"
                                wire:input.debounce.500ms="parseInput"
                                class="form-textarea-dark w-full h-64 font-mono text-sm"
                                placeholder="{{ $importMode === 'sku_only'
                                    ? "SKU001\nSKU002\nSKU003"
                                    : "SKU001;Nazwa produktu 1\nSKU002;Nazwa produktu 2" }}"
                            ></textarea>
                        </div>

                        {{-- Stats for Single Column --}}
                        @if($stats['total_lines'] > 0)
                            <div class="flex flex-wrap gap-3 text-sm">
                                <span class="px-2 py-1 bg-gray-700 rounded text-gray-300">
                                    Linii: {{ $stats['total_lines'] }}
                                </span>
                                <span class="px-2 py-1 bg-green-900/50 rounded text-green-400">
                                    Poprawnych: {{ $stats['valid_items'] }}
                                </span>
                                @if($stats['skipped_empty'] > 0)
                                    <span class="px-2 py-1 bg-gray-700 rounded text-gray-400">
                                        Pustych: {{ $stats['skipped_empty'] }}
                                    </span>
                                @endif
                                @if($stats['duplicates_in_batch'] > 0)
                                    <span class="px-2 py-1 bg-yellow-900/50 rounded text-yellow-400">
                                        Duplikatow: {{ $stats['duplicates_in_batch'] }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    @endif

                    {{-- Input Area - Two Column Mode --}}
                    @if($importMode === 'sku_name' && isset($viewMode) && $viewMode === 'two_columns')
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                Wklej dane oddzielnie
                                <span class="text-gray-500 font-normal text-xs">
                                    (każda pozycja w nowej linii)
                                </span>
                            </label>

                            {{-- Two Column Grid --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Left: SKU Column --}}
                                <div class="space-y-2">
                                    <label class="block text-xs font-medium text-gray-400">
                                        SKU
                                        <span class="text-gray-600">(wymagane)</span>
                                    </label>
                                    <textarea
                                        wire:model.live.debounce.500ms="rawSkuInput"
                                        wire:input.debounce.500ms="parseTwoColumnInput"
                                        class="form-textarea-dark w-full h-56 font-mono text-sm"
                                        placeholder="SKU001&#10;SKU002&#10;SKU003"
                                    ></textarea>
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-gray-400">
                                            Pozycji: <span class="font-semibold text-gray-300">{{ $this->getSkuCount() }}</span>
                                        </span>
                                    </div>
                                </div>

                                {{-- Right: Names Column --}}
                                <div class="space-y-2">
                                    <label class="block text-xs font-medium text-gray-400">
                                        Nazwy
                                        <span class="text-gray-600">(opcjonalne)</span>
                                    </label>
                                    <textarea
                                        wire:model.live.debounce.500ms="rawNameInput"
                                        wire:input.debounce.500ms="parseTwoColumnInput"
                                        class="form-textarea-dark w-full h-56 font-mono text-sm"
                                        placeholder="Nazwa produktu 1&#10;Nazwa produktu 2&#10;Nazwa produktu 3"
                                    ></textarea>
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-gray-400">
                                            Pozycji: <span class="font-semibold text-gray-300">{{ $this->getNameCount() }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Count Mismatch Warning --}}
                            @if($this->hasCountMismatch())
                                <div class="mt-3 p-3 bg-yellow-900/30 border border-yellow-700/50 rounded-lg flex items-start gap-3">
                                    <svg class="w-5 h-5 text-yellow-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-yellow-400">Niezgodnosc liczby pozycji</p>
                                        <p class="text-xs text-yellow-300 mt-1">
                                            SKU: <span class="font-semibold">{{ $this->getSkuCount() }}</span> |
                                            Nazwy: <span class="font-semibold">{{ $this->getNameCount() }}</span>
                                        </p>
                                        <p class="text-xs text-gray-400 mt-1">
                                            Pozycje bez nazwy otrzymają automatyczną nazwę na podstawie SKU
                                        </p>
                                    </div>
                                </div>
                            @endif

                            {{-- Stats for Two Column --}}
                            @if($this->getSkuCount() > 0)
                                <div class="flex flex-wrap gap-3 text-sm mt-3">
                                    <span class="import-stat-badge import-stat-badge-valid">
                                        Poprawnych: {{ $this->getValidCount() }}
                                    </span>
                                    @if($this->getEmptySkuCount() > 0)
                                        <span class="import-stat-badge import-stat-badge-warning">
                                            Pustych SKU: {{ $this->getEmptySkuCount() }}
                                        </span>
                                    @endif
                                    @if($this->getDuplicatesCount() > 0)
                                        <span class="import-stat-badge import-stat-badge-error">
                                            Duplikatow: {{ $this->getDuplicatesCount() }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Right Column: Preview --}}
                <div class="space-y-4">
                    {{-- Preview Header --}}
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-gray-300">Podglad rozpoznanych danych</h3>
                        @if(count($parsedItems) > 0)
                            <span class="text-xs text-gray-500">
                                {{ count($parsedItems) }} pozycji
                                @if($importableCount < count($parsedItems))
                                    ({{ $importableCount }} do importu)
                                @endif
                            </span>
                        @endif
                    </div>

                    {{-- Errors --}}
                    @if(count($errors) > 0)
                        <div class="bg-red-900/30 border border-red-700/50 rounded-lg p-3">
                            <h4 class="text-sm font-medium text-red-400 mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Bledy ({{ count($errors) }})
                            </h4>
                            <ul class="text-xs text-red-300 space-y-1 max-h-24 overflow-y-auto">
                                @foreach(array_slice($errors, 0, 5) as $error)
                                    <li>
                                        <span class="text-red-400">Linia {{ $error['line'] }}:</span>
                                        {{ $error['message'] }}
                                        @if(isset($error['sku']))
                                            <code class="text-red-200">"{{ $error['sku'] }}"</code>
                                        @endif
                                    </li>
                                @endforeach
                                @if(count($errors) > 5)
                                    <li class="text-red-400">... i {{ count($errors) - 5 }} wiecej</li>
                                @endif
                            </ul>
                        </div>
                    @endif

                    {{-- Warnings --}}
                    @if(count($warnings) > 0)
                        <div class="bg-yellow-900/30 border border-yellow-700/50 rounded-lg p-3">
                            <h4 class="text-sm font-medium text-yellow-400 mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                Ostrzezenia ({{ count($warnings) }})
                            </h4>
                            <ul class="text-xs text-yellow-300 space-y-1 max-h-24 overflow-y-auto">
                                @foreach(array_slice($warnings, 0, 5) as $warning)
                                    <li>{{ $warning['message'] }}</li>
                                @endforeach
                                @if(count($warnings) > 5)
                                    <li class="text-yellow-400">... i {{ count($warnings) - 5 }} wiecej</li>
                                @endif
                            </ul>
                        </div>
                    @endif

                    {{-- Preview Table --}}
                    @if(count($parsedItems) > 0)
                        <div class="bg-gray-800/50 rounded-lg border border-gray-700/50 overflow-hidden">
                            <div class="max-h-64 overflow-y-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-800 sticky top-0">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-400">#</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-400">SKU</th>
                                            @if($importMode === 'sku_name')
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-400">Nazwa</th>
                                            @endif
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-400">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-700/50">
                                        @foreach(array_slice($parsedItems, 0, 20) as $index => $item)
                                            @php
                                                $skuUpper = strtoupper(trim($item['sku']));
                                                $inPPM = isset($existingInPPM[$skuUpper]);
                                                $inPending = isset($existingInPending[$skuUpper]);
                                                $hasConflict = $inPPM || $inPending;
                                                $missingName = $importMode === 'sku_name' && empty($item['name']);
                                            @endphp
                                            <tr class="{{ $hasConflict ? 'bg-yellow-900/20' : '' }}">
                                                <td class="px-3 py-2 text-gray-500">{{ $item['line'] }}</td>
                                                <td class="px-3 py-2 font-mono text-gray-300">
                                                    {{ $item['sku'] }}
                                                </td>
                                                @if($importMode === 'sku_name')
                                                    <td class="px-3 py-2 text-gray-400 truncate max-w-[200px]">
                                                        @if($missingName)
                                                            <span class="text-yellow-400 italic text-xs">Brak nazwy</span>
                                                        @else
                                                            {{ $item['name'] }}
                                                        @endif
                                                    </td>
                                                @endif
                                                <td class="px-3 py-2">
                                                    @if($inPPM)
                                                        <span class="import-stat-badge import-stat-badge-error">
                                                            Istnieje
                                                        </span>
                                                    @elseif($inPending)
                                                        <span class="import-stat-badge import-stat-badge-warning">
                                                            Oczekuje
                                                        </span>
                                                    @elseif($missingName)
                                                        <span class="import-stat-badge import-stat-badge-warning">
                                                            Bez nazwy
                                                        </span>
                                                    @else
                                                        <span class="import-stat-badge import-stat-badge-valid">
                                                            OK
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if(count($parsedItems) > 20)
                                <div class="px-3 py-2 text-xs text-center text-gray-500 bg-gray-800/50 border-t border-gray-700/50">
                                    Wyswietlono 20 z {{ count($parsedItems) }} pozycji
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="bg-gray-800/30 rounded-lg border border-gray-700/30 p-8 text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="text-gray-500 text-sm">Wklej liste SKU w polu po lewej stronie</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="modal-footer-enterprise">
            <div class="flex items-center justify-between w-full">
                <div class="text-sm text-gray-400">
                    @if($importableCount > 0)
                        <span class="text-green-400">{{ $importableCount }}</span> pozycji gotowych do importu
                    @endif
                </div>
                <div class="flex gap-3">
                    <button wire:click="close" class="btn-enterprise-ghost">
                        Anuluj
                    </button>
                    <button
                        wire:click="import"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        class="btn-enterprise-primary"
                        {{ $importableCount === 0 ? 'disabled' : '' }}
                    >
                        <span wire:loading.remove wire:target="import">
                            Importuj {{ $importableCount }} pozycji
                        </span>
                        <span wire:loading wire:target="import" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Importowanie...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{--
    CSV Mode - Text paste / file upload with 3-step flow:
    Step 1: Input (paste text or upload file)
    Step 2: Mapping (auto-detected + manual column mapping)
    Step 3: Preview (table with first rows + confirmation)

    Expected Livewire properties (from ImportModalCsvModeTrait):
    - $csvTextInput (string) - raw pasted text
    - $csvMappingStep (bool) - wizard step 2 active
    - $csvPreviewStep (bool) - wizard step 3 active
    - $csvParsedHeaders (array) - parsed header names from first row
    - $csvDetectedMapping (array) - auto-detected mapping: col_index => ppm_field
    - $csvManualMapping (array) - user-adjusted mapping: col_index => ppm_field
    - $csvSampleValues (array) - sample values from first data row per col_index
    - $csvPreviewHeaders (array) - preview headers: field_key => label
    - $csvPreviewRows (array) - preview rows (max 10): array of [field_key => value]
    - $csvTotalRows (int) - total parsed rows count

    Expected from render() viewData:
    - $csvMappableFields (array) - available PPM fields for mapping dropdown
--}}
<div class="space-y-6">

    {{-- ================================================================
         STEP INDICATOR
         ================================================================ --}}
    <div class="flex items-center justify-center gap-4">
        {{-- Step 1: Input --}}
        <div class="flex items-center gap-2">
            <div class="flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold
                        {{ !($csvMappingStep ?? false) && !($csvPreviewStep ?? false)
                            ? 'bg-amber-500 text-white'
                            : 'bg-green-600 text-white' }}">
                @if(($csvMappingStep ?? false) || ($csvPreviewStep ?? false))
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                @else
                    1
                @endif
            </div>
            <span class="text-sm {{ !($csvMappingStep ?? false) && !($csvPreviewStep ?? false) ? 'text-white font-medium' : 'text-gray-400' }}">
                Dane
            </span>
        </div>

        <div class="w-10 h-0.5 {{ ($csvMappingStep ?? false) || ($csvPreviewStep ?? false) ? 'bg-green-600' : 'bg-gray-600' }}"></div>

        {{-- Step 2: Mapping --}}
        <div class="flex items-center gap-2">
            <div class="flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold
                        {{ ($csvMappingStep ?? false) && !($csvPreviewStep ?? false)
                            ? 'bg-amber-500 text-white'
                            : (($csvPreviewStep ?? false) ? 'bg-green-600 text-white' : 'bg-gray-600 text-gray-400') }}">
                @if($csvPreviewStep ?? false)
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                @else
                    2
                @endif
            </div>
            <span class="text-sm {{ ($csvMappingStep ?? false) && !($csvPreviewStep ?? false) ? 'text-white font-medium' : 'text-gray-400' }}">
                Mapowanie
            </span>
        </div>

        <div class="w-10 h-0.5 {{ ($csvPreviewStep ?? false) ? 'bg-green-600' : 'bg-gray-600' }}"></div>

        {{-- Step 3: Preview --}}
        <div class="flex items-center gap-2">
            <div class="flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold
                        {{ ($csvPreviewStep ?? false)
                            ? 'bg-amber-500 text-white'
                            : 'bg-gray-600 text-gray-400' }}">
                3
            </div>
            <span class="text-sm {{ ($csvPreviewStep ?? false) ? 'text-white font-medium' : 'text-gray-400' }}">
                Podglad
            </span>
        </div>
    </div>

    {{-- ================================================================
         STEP 1: INPUT (Text paste + File upload)
         ================================================================ --}}
    @if(!($csvMappingStep ?? false) && !($csvPreviewStep ?? false))
        <div class="space-y-4">

            {{-- Template header reference line --}}
            <div class="p-3 bg-gray-900/60 rounded-lg border border-gray-700/50">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">Format naglowkow (separator: srednik)</span>
                </div>
                <code class="block text-xs text-gray-500 font-mono overflow-x-auto whitespace-nowrap pb-1">
                    SKU;Nazwa;Typ produktu;Kod Dostawcy;Producent;Importer;Cena zakupu netto;Cena bazowa netto;VAT;EAN;Waga
                </code>
            </div>

            {{-- Error messages for CSV input --}}
            @if($errors->has('csvTextInput') || $errors->has('csvFile') || $errors->has('csvMapping'))
                <div class="p-3 bg-red-500/15 border border-red-500/30 rounded-lg">
                    <div class="flex items-start gap-2">
                        <svg class="w-4 h-4 text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div class="text-sm text-red-300">
                            @error('csvTextInput') <p>{{ $message }}</p> @enderror
                            @error('csvFile') <p>{{ $message }}</p> @enderror
                            @error('csvMapping') <p>{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            @endif

            {{-- Textarea for pasting CSV data --}}
            <div>
                <label for="csv-text-input" class="block text-sm font-medium text-gray-300 mb-2">
                    Wklej dane CSV
                    <span class="text-gray-500 font-normal">(rozdzielone srednikiem, tabulatorem lub przecinkiem)</span>
                </label>
                <textarea
                    id="csv-text-input"
                    wire:model.live="csvTextInput"
                    class="form-textarea-dark w-full h-48 font-mono text-sm"
                    placeholder="SKU001;Nazwa produktu 1;Typ;DOSTAWCA01;Honda;Importer1;100.00;150.00;23;5901234123457;2.5&#10;SKU002;Nazwa produktu 2;Typ;DOSTAWCA02;Yamaha;Importer2;200.00;300.00;23;5901234123458;3.0"
                ></textarea>
            </div>

            {{-- OR divider --}}
            <div class="flex items-center gap-4">
                <div class="flex-1 h-px bg-gray-700"></div>
                <span class="text-xs text-gray-500 uppercase tracking-wider">lub</span>
                <div class="flex-1 h-px bg-gray-700"></div>
            </div>

            {{-- File upload zone --}}
            <div x-data="{
                    isDragging: false,
                    handleDrop(e) {
                        this.isDragging = false;
                        const files = e.dataTransfer.files;
                        if (files.length > 0) {
                            $wire.upload('csvFile', files[0]);
                        }
                    }
                }"
                 @dragover.prevent="isDragging = true"
                 @dragleave.prevent="isDragging = false"
                 @drop.prevent="handleDrop($event)"
                 :class="{ 'border-amber-500 bg-amber-500/10': isDragging }"
                 class="border-2 border-dashed border-gray-600 rounded-lg p-6 text-center transition-all hover:border-gray-500 cursor-pointer">
                <div class="flex flex-col items-center">
                    <svg class="w-8 h-8 text-gray-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p class="text-sm text-gray-400 mb-1">Przeciagnij plik CSV/Excel tutaj</p>
                    <label for="csv-file-input"
                           class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-gray-700 hover:bg-gray-600 rounded-lg cursor-pointer transition-colors mt-1">
                        Wybierz plik
                    </label>
                    <input id="csv-file-input"
                           type="file"
                           wire:model="csvFile"
                           accept=".csv,.xlsx,.xls,.txt"
                           class="hidden">
                    <p class="text-xs text-gray-600 mt-2">CSV, XLSX, XLS | Max 50MB</p>
                </div>

                {{-- Upload progress --}}
                <div wire:loading wire:target="csvFile" class="mt-3">
                    <div class="flex items-center justify-center gap-2 text-gray-400">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span class="text-sm">Przetwarzanie pliku...</span>
                    </div>
                </div>
            </div>

            {{-- Parse button --}}
            <div class="flex items-center justify-between">
                <p class="text-xs text-gray-500">
                    System automatycznie wykryje separator i kodowanie znakow.
                </p>
                <button wire:click="parseCsvData"
                        wire:loading.attr="disabled"
                        wire:target="parseCsvData"
                        class="btn-enterprise-primary"
                        @if(empty($csvTextInput) && !isset($csvFile))
                            disabled
                        @endif>
                    <span wire:loading.remove wire:target="parseCsvData">
                        Parsuj dane
                    </span>
                    <span wire:loading wire:target="parseCsvData" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Parsowanie...
                    </span>
                </button>
            </div>
        </div>
    @endif

    {{-- ================================================================
         STEP 2: COLUMN MAPPING
         ================================================================ --}}
    @if(($csvMappingStep ?? false) && !($csvPreviewStep ?? false))
        <div class="space-y-4">
            {{-- Summary --}}
            <div class="flex items-center justify-between p-3 bg-gray-900/40 rounded-lg border border-gray-700/50">
                <div class="text-sm text-gray-300">
                    Automatycznie rozpoznano
                    <span class="font-semibold text-green-400">{{ count(array_filter($csvDetectedMapping)) }}</span>
                    z
                    <span class="font-semibold">{{ count($csvParsedHeaders) }}</span>
                    kolumn
                </div>
                <div class="flex items-center gap-3">
                    <button wire:click="resetCsvMapping"
                            class="text-xs text-gray-400 hover:text-white transition-colors">
                        Resetuj mapowanie
                    </button>
                    <button wire:click="goBackToCsvInput"
                            class="text-xs text-gray-400 hover:text-white transition-colors">
                        Wstecz
                    </button>
                </div>
            </div>

            {{-- Mapping Table --}}
            <div class="overflow-x-auto rounded-lg border border-gray-700/50">
                <table class="w-full text-sm">
                    <thead class="bg-gray-900/50">
                        <tr class="border-b border-gray-700">
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                Kolumna z CSV
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                Przykladowa wartosc
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                Pole PPM
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider w-24">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700/50">
                        @foreach($csvParsedHeaders as $headerIndex => $header)
                            @php
                                $currentMapping = $csvManualMapping[$headerIndex] ?? '';
                                $isAutoMapped = !empty($csvDetectedMapping[$headerIndex] ?? '');
                                $sampleValue = $csvSampleValues[$headerIndex] ?? '-';
                            @endphp
                            <tr class="hover:bg-gray-700/20 transition-colors">
                                {{-- CSV Column Name --}}
                                <td class="px-4 py-3">
                                    <span class="text-gray-200 font-medium">{{ $header }}</span>
                                </td>

                                {{-- Sample Value --}}
                                <td class="px-4 py-3">
                                    <span class="text-gray-400 truncate block max-w-[200px] font-mono text-xs">
                                        {{ $sampleValue }}
                                    </span>
                                </td>

                                {{-- PPM Field Dropdown --}}
                                <td class="px-4 py-3">
                                    <select wire:change="setCsvManualMapping({{ $headerIndex }}, $event.target.value)"
                                            class="form-select-dark-sm w-full">
                                        <option value="">-- Ignoruj --</option>
                                        @foreach(($csvMappableFields ?? []) as $fieldKey => $fieldLabel)
                                            <option value="{{ $fieldKey }}"
                                                    @if($currentMapping === $fieldKey) selected @endif>
                                                {{ $fieldLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                {{-- Auto-mapped badge --}}
                                <td class="px-4 py-3 text-center">
                                    @if(!empty($currentMapping))
                                        @if($isAutoMapped)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-500/20 text-green-400">
                                                Auto
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-500/20 text-blue-400">
                                                Reczne
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-gray-600 text-xs">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mapping error --}}
            @error('csvMapping')
                <div class="p-3 bg-red-500/15 border border-red-500/30 rounded-lg">
                    <p class="text-sm text-red-300">{{ $message }}</p>
                </div>
            @enderror

            {{-- Confirm Mapping Button --}}
            <div class="flex justify-end">
                <button wire:click="confirmCsvMapping"
                        wire:loading.attr="disabled"
                        wire:target="confirmCsvMapping"
                        class="btn-enterprise-primary">
                    <span wire:loading.remove wire:target="confirmCsvMapping">
                        Potwierdz mapowanie
                    </span>
                    <span wire:loading wire:target="confirmCsvMapping" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Przetwarzanie...
                    </span>
                </button>
            </div>
        </div>
    @endif

    {{-- ================================================================
         STEP 3: PREVIEW
         ================================================================ --}}
    @if($csvPreviewStep ?? false)
        <div class="space-y-4">
            {{-- Summary --}}
            <div class="flex items-center justify-between p-3 bg-green-500/10 rounded-lg border border-green-500/30">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm text-green-300">
                        <span class="font-semibold">{{ $csvTotalRows ?? 0 }}</span> produktow gotowych do importu
                    </span>
                </div>
                <button wire:click="goBackToCsvMapping"
                        class="text-xs text-gray-400 hover:text-white transition-colors">
                    Wstecz do mapowania
                </button>
            </div>

            {{-- Preview Table --}}
            <div class="overflow-x-auto rounded-lg border border-gray-700/50">
                <table class="w-full text-sm">
                    <thead class="bg-gray-900/50">
                        <tr class="border-b border-gray-700">
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-400">#</th>
                            @foreach(($csvPreviewHeaders ?? []) as $fieldKey => $fieldLabel)
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-400 min-w-[120px]">
                                    {{ $fieldLabel }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700/50">
                        @foreach(($csvPreviewRows ?? []) as $rowIndex => $row)
                            <tr class="hover:bg-gray-700/20 transition-colors">
                                <td class="px-3 py-2 text-gray-500 font-mono text-xs">{{ $rowIndex + 1 }}</td>
                                @foreach(($csvPreviewHeaders ?? []) as $fieldKey => $fieldLabel)
                                    <td class="px-3 py-2 text-gray-300 truncate max-w-[200px]">
                                        {{ $row[$fieldKey] ?? '-' }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Note --}}
            @if(($csvTotalRows ?? 0) > count($csvPreviewRows ?? []))
                <p class="text-xs text-gray-500 text-center">
                    Wyswietlono {{ count($csvPreviewRows ?? []) }} z {{ $csvTotalRows ?? 0 }} wierszy
                </p>
            @endif
        </div>
    @endif

</div>

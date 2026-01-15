{{-- Preview Table Partial --}}
<div class="csv-preview-table">
    {{-- Header --}}
    <div class="mb-4">
        <h3 class="text-lg font-medium text-white">Podglad danych do importu</h3>
        <p class="text-sm text-slate-400 mt-1">
            {{ $this->getPreviewSummary() }}
        </p>
    </div>

    {{-- Large file warning --}}
    @if($this->getLargeFileWarning())
        <div class="mb-4 p-3 bg-amber-500/20 border border-amber-500/50 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-amber-400 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <span class="text-sm text-amber-300">{{ $this->getLargeFileWarning() }}</span>
            </div>
        </div>
    @endif

    {{-- Mapped columns summary --}}
    <div class="mb-4 p-3 bg-slate-700/50 rounded-lg">
        <div class="text-sm text-slate-300 mb-2">Zmapowane pola:</div>
        <div class="flex flex-wrap gap-2">
            @foreach($columnMapping as $excelColumn => $ppmField)
                @if(!empty($ppmField))
                    <span class="inline-flex items-center px-2 py-1 text-xs bg-slate-600 text-slate-200 rounded">
                        <span class="text-slate-400 mr-1">{{ $excelColumn }}</span>
                        <svg class="w-3 h-3 text-slate-500 mx-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                        <span class="text-green-400 font-medium">{{ $availablePPMFields[$ppmField] ?? $ppmField }}</span>
                    </span>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Preview Table --}}
    @php
        $mappedColumns = $this->getMappedColumnsForPreview();
        $mappedPreviewRows = $this->getMappedPreviewRows();
    @endphp

    @if(!empty($mappedColumns) && !empty($mappedPreviewRows))
        <div class="overflow-x-auto border border-slate-700 rounded-lg">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-700">
                        <th class="px-3 py-2 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">
                            #
                        </th>
                        @foreach($mappedColumns as $excelColumn)
                            <th class="px-3 py-2 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">
                                {{ $availablePPMFields[$columnMapping[$excelColumn]] ?? $columnMapping[$excelColumn] }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    @foreach($mappedPreviewRows as $index => $row)
                        <tr class="hover:bg-slate-700/30 transition-colors">
                            <td class="px-3 py-2 text-sm text-slate-500">
                                {{ $index + 1 }}
                            </td>
                            @foreach($mappedColumns as $excelColumn)
                                @php
                                    $ppmField = $columnMapping[$excelColumn];
                                    $value = $row[$ppmField] ?? '';
                                @endphp
                                <td class="px-3 py-2 text-sm {{ $ppmField === 'sku' ? 'text-orange-400 font-medium' : 'text-slate-300' }}">
                                    @if(strlen($value) > 50)
                                        <span title="{{ $value }}">{{ substr($value, 0, 47) }}...</span>
                                    @else
                                        {{ $value ?: '-' }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Preview info --}}
        <div class="mt-3 text-xs text-slate-500">
            Pokazano {{ count($mappedPreviewRows) }} z {{ $this->getTotalRowCount() }} wierszy
        </div>
    @else
        <div class="p-8 text-center text-slate-400">
            <svg class="w-12 h-12 mx-auto mb-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p>Brak danych do podgladu</p>
            <p class="text-sm mt-1">Upewnij sie, ze zmapowales przynajmniej kolumne SKU</p>
        </div>
    @endif

    {{-- Import summary --}}
    <div class="mt-6 p-4 bg-slate-700/30 rounded-lg border border-slate-600">
        <h4 class="text-sm font-medium text-white mb-3">Podsumowanie importu:</h4>

        <div class="grid grid-cols-3 gap-4">
            <div class="text-center">
                <div class="text-2xl font-bold text-white">{{ count($this->getMappedRows()) }}</div>
                <div class="text-xs text-slate-400">produktow do importu</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-green-400">{{ $this->getMappedColumnCount() }}</div>
                <div class="text-xs text-slate-400">zmapowanych pol</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-slate-400">{{ $this->getUnmappedColumnCount() }}</div>
                <div class="text-xs text-slate-400">pominiete kolumny</div>
            </div>
        </div>
    </div>
</div>

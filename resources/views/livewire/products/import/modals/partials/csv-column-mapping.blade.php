{{-- Column Mapping Partial --}}
<div class="csv-column-mapping">
    {{-- Header --}}
    <div class="mb-4">
        <h3 class="text-lg font-medium text-white">Mapowanie kolumn</h3>
        <p class="text-sm text-slate-400 mt-1">
            Przypisz kolumny z pliku do pol PPM. Pole <span class="text-orange-400 font-medium">SKU jest wymagane</span>.
        </p>
    </div>

    {{-- Auto-mapping summary --}}
    <div class="mb-4 p-3 bg-slate-700/50 rounded-lg">
        <div class="flex items-center justify-between">
            <div class="text-sm text-slate-300">
                Automatycznie rozpoznano
                <span class="font-medium text-green-400">{{ $this->getMappedColumnCount() }}</span>
                z
                <span class="font-medium">{{ count($parsedData['headers']) }}</span>
                kolumn
            </div>
            <div class="flex items-center space-x-3">
                <button
                    wire:click="resetMapping"
                    class="text-xs text-slate-400 hover:text-white transition-colors"
                >
                    Resetuj wszystko
                </button>
                <button
                    wire:click="applyAutoMapping"
                    class="text-xs text-orange-400 hover:text-orange-300 transition-colors"
                >
                    Zastosuj auto-mapowanie
                </button>
            </div>
        </div>

        {{-- SKU warning --}}
        @if(!$this->isSkuMapped())
            <div class="mt-2 flex items-center text-amber-400 text-xs">
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                Musisz zmapowac kolumne SKU aby kontynuowac
            </div>
        @endif
    </div>

    {{-- Mapping Table --}}
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-slate-700">
                    <th class="px-3 py-2 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">
                        Kolumna w pliku
                    </th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">
                        Przykladowa wartosc
                    </th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">
                        Pole PPM
                    </th>
                    <th class="px-3 py-2 text-center text-xs font-medium text-slate-400 uppercase tracking-wider">
                        Pewnosc
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700">
                @foreach($parsedData['headers'] as $excelHeader)
                    <tr class="hover:bg-slate-700/30 transition-colors">
                        {{-- Excel Column Name --}}
                        <td class="px-3 py-3">
                            <div class="flex items-center">
                                <span class="text-sm text-slate-300 font-medium">{{ $excelHeader }}</span>
                            </div>
                        </td>

                        {{-- Sample Value --}}
                        <td class="px-3 py-3">
                            <span class="text-sm text-slate-400 truncate max-w-[200px] block">
                                {{ $this->getSampleValue($excelHeader) ?: '-' }}
                            </span>
                        </td>

                        {{-- PPM Field Dropdown --}}
                        <td class="px-3 py-3">
                            <select
                                wire:change="updateColumnMapping('{{ $excelHeader }}', $event.target.value)"
                                class="w-full px-3 py-2 text-sm bg-slate-700 border border-slate-600 rounded-lg text-white focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition-colors"
                            >
                                @foreach($availablePPMFields as $fieldKey => $fieldLabel)
                                    <option
                                        value="{{ $fieldKey }}"
                                        @if(($columnMapping[$excelHeader] ?? '') === $fieldKey) selected @endif
                                    >
                                        {{ $fieldLabel }}
                                    </option>
                                @endforeach
                            </select>

                            {{-- Suggestions --}}
                            @php
                                $suggestions = $this->getAlternativeSuggestions($excelHeader);
                            @endphp
                            @if(!empty($suggestions) && empty($columnMapping[$excelHeader]))
                                <div class="mt-1 flex flex-wrap gap-1">
                                    <span class="text-xs text-slate-500">Sugestie:</span>
                                    @foreach($suggestions as $suggestion)
                                        <button
                                            wire:click="updateColumnMapping('{{ $excelHeader }}', '{{ $suggestion }}')"
                                            class="text-xs text-orange-400 hover:text-orange-300 transition-colors"
                                        >
                                            {{ $availablePPMFields[$suggestion] ?? $suggestion }}
                                        </button>
                                        @if(!$loop->last)
                                            <span class="text-slate-600">,</span>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </td>

                        {{-- Confidence Badge --}}
                        <td class="px-3 py-3 text-center">
                            @php
                                $confidence = $this->getConfidencePercent($excelHeader);
                                $badgeClass = $this->getConfidenceBadgeClass($excelHeader);
                            @endphp

                            @if($confidence > 0)
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                                    @if($confidence >= 90) bg-green-500/20 text-green-400
                                    @elseif($confidence >= 70) bg-blue-500/20 text-blue-400
                                    @elseif($confidence >= 50) bg-yellow-500/20 text-yellow-400
                                    @else bg-slate-500/20 text-slate-400
                                    @endif
                                ">
                                    {{ $confidence }}%
                                </span>
                            @else
                                <span class="text-slate-500 text-xs">-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Legend --}}
    <div class="mt-4 pt-4 border-t border-slate-700">
        <div class="flex items-center space-x-6 text-xs text-slate-500">
            <div class="flex items-center">
                <span class="w-3 h-3 rounded-full bg-green-500/20 mr-2"></span>
                <span>90%+ bardzo wysoka pewnosc</span>
            </div>
            <div class="flex items-center">
                <span class="w-3 h-3 rounded-full bg-blue-500/20 mr-2"></span>
                <span>70-89% auto-mapowane</span>
            </div>
            <div class="flex items-center">
                <span class="w-3 h-3 rounded-full bg-yellow-500/20 mr-2"></span>
                <span>50-69% sugestia</span>
            </div>
        </div>
    </div>
</div>

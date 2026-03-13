<div>
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-white">Podglad eksportu</h2>
            <p class="mt-1 text-sm text-gray-400">
                Podglad pierwszych 5 produktow
                @if($previewCount > 0)
                    <span class="text-[#e0ac7e]">(z {{ number_format($previewCount) }} znalezionych)</span>
                @endif
            </p>
        </div>
        <button wire:click="loadPreview"
                wire:loading.attr="disabled"
                wire:target="loadPreview"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-600 px-3 py-1.5 text-sm text-gray-300 transition-colors hover:bg-gray-700 hover:text-white">
            <svg wire:loading.remove wire:target="loadPreview" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <svg wire:loading wire:target="loadPreview" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            Odswiez podglad
        </button>
    </div>

    {{-- Summary --}}
    <div class="mb-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-lg border border-gray-700 bg-gray-800/30 p-3 text-center">
            <p class="text-xs text-gray-400">Produkty</p>
            <p class="text-lg font-bold text-white">{{ number_format($previewCount) }}</p>
        </div>
        <div class="rounded-lg border border-gray-700 bg-gray-800/30 p-3 text-center">
            <p class="text-xs text-gray-400">Kolumny</p>
            <p class="text-lg font-bold text-white">{{ $this->getSelectedCount() }}</p>
        </div>
        <div class="rounded-lg border border-gray-700 bg-gray-800/30 p-3 text-center">
            <p class="text-xs text-gray-400">Format</p>
            <p class="text-lg font-bold text-white uppercase">{{ $format }}</p>
        </div>
        <div class="rounded-lg border border-gray-700 bg-gray-800/30 p-3 text-center">
            <p class="text-xs text-gray-400">Harmonogram</p>
            <p class="text-lg font-bold text-white">{{ $schedule === 'manual' ? 'Reczny' : $schedule }}</p>
        </div>
    </div>

    {{-- Preview Table --}}
    @if(count($previewProducts) > 0)
        <div class="overflow-x-auto rounded-lg border border-gray-700">
            <table class="w-full text-sm text-gray-300">
                <thead>
                    <tr class="border-b border-gray-700 bg-gray-800/80">
                        @foreach(array_keys($previewProducts[0] ?? []) as $header)
                            <th class="px-3 py-2 text-left text-xs font-medium uppercase text-gray-400">
                                {{ $header }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($previewProducts as $index => $row)
                        <tr wire:key="preview-row-{{ $index }}" class="border-b border-gray-700/50 transition-colors hover:bg-gray-800/30">
                            @foreach($row as $value)
                                <td class="whitespace-nowrap px-3 py-2 text-xs">
                                    {{ Str::limit((string) $value, 50) }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="rounded-lg border border-gray-700 bg-gray-800/30 px-6 py-8 text-center">
            <svg class="mx-auto mb-3 h-10 w-10 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <p class="text-sm text-gray-500">Brak produktow spelniajacych kryteria filtrowania.</p>
        </div>
    @endif
</div>

{{-- Variant image preview section - compact thumbnail overview --}}
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

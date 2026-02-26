@if(!empty($brandSuggestions) || (!empty($dismissedSuggestions) && $showDismissedSuggestions))

    {{-- Aktywne sugestie --}}
    @foreach($brandSuggestions ?? [] as $entry)
        @php $suggestions = $entry['suggestions']; @endphp
        @if($suggestions->isNotEmpty())
        <div class="mb-3 matrix-brand-suggestion">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 flex-shrink-0 matrix-brand-suggestion-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="matrix-brand-suggestion-title">
                        Sugestie marek dla {{ $entry['shop_name'] }}:
                    </span>
                </div>
            </div>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach($suggestions->take(8) as $suggestion)
                    <div class="matrix-brand-chip">
                        <span class="matrix-brand-chip-text">
                            {{ $suggestion->manufacturerRelation?->name ?? 'N/A' }}
                            ({{ $suggestion->product_count }})
                        </span>
                        <button
                            wire:click="$set('brandFilter', {{ $suggestion->manufacturer_id }})"
                            class="matrix-brand-chip-show">
                            Wyswietl
                        </button>
                        <button
                            wire:click="addBrandToAllowed('{{ addslashes($suggestion->manufacturerRelation?->name) }}', {{ $entry['shop_id'] }})"
                            class="matrix-brand-chip-add">
                            Dodaj
                        </button>
                        <button
                            wire:click="dismissBrandSuggestion('{{ addslashes($suggestion->manufacturerRelation?->name) }}', {{ $entry['shop_id'] }})"
                            class="matrix-brand-chip-dismiss">
                            Ignoruj
                        </button>
                    </div>
                @endforeach
                @if($suggestions->count() > 8)
                    <span class="text-xs text-gray-500 self-center">
                        +{{ $suggestions->count() - 8 }} wiecej
                    </span>
                @endif
            </div>
        </div>
        @endif
    @endforeach

    {{-- Przelacznik odrzuconych sugestii --}}
    @if(!empty($dismissedSuggestions))
        @php
            $dismissedTotal = collect($dismissedSuggestions)->sum(fn($e) => $e['suggestions']->count());
        @endphp
        <div class="mb-3 flex items-center space-x-2">
            <button
                wire:click="toggleDismissedSuggestions"
                class="matrix-brand-toggle-dismissed">
                @if($showDismissedSuggestions)
                    Ukryj ignorowane sugestie
                @else
                    Pokaz ignorowane ({{ $dismissedTotal }})
                @endif
            </button>
        </div>
    @endif

    {{-- Odrzucone sugestie (jesli wlaczone) --}}
    @if($showDismissedSuggestions && !empty($dismissedSuggestions))
        @foreach($dismissedSuggestions as $entry)
            @php $suggestions = $entry['suggestions']; @endphp
            @if($suggestions->isNotEmpty())
            <div class="mb-3 matrix-brand-dismissed">
                <div class="flex items-center space-x-2 mb-2">
                    <span class="text-xs text-gray-500">
                        Ignorowane dla {{ $entry['shop_name'] }}:
                    </span>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach($suggestions as $suggestion)
                        <div class="matrix-brand-dismissed-chip">
                            <span class="text-xs text-gray-500">
                                {{ $suggestion->manufacturerRelation?->name ?? 'N/A' }}
                                ({{ $suggestion->product_count }})
                            </span>
                            <button
                                wire:click="restoreBrandSuggestion('{{ addslashes($suggestion->manufacturerRelation?->name) }}', {{ $entry['shop_id'] }})"
                                class="matrix-brand-restore">
                                Przywroc
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        @endforeach
    @endif

@endif

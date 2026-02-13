{{-- Partial: Description tab content (reusable textarea pair)
     Props:
     - $tabType: 'default' | 'shop'
     - $shopId: int|null (only for shop tabs)
     - $shopName: string|null (only for shop tabs)
     - $shortModel: wire:model path for short description
     - $longModel: wire:model path for long description
     - $shortValue: current short description value
     - $longValue: current long description value
     - $skipDescriptions: bool
--}}

@php
    $shortLen = strlen($shortValue ?? '');
    $longLen = strlen($longValue ?? '');
    $isShopTab = ($tabType === 'shop');
    $hasContent = !empty(trim($shortValue ?? '')) || !empty(trim($longValue ?? ''));
@endphp

{{-- Inherit banner (shop tabs only) --}}
@if($isShopTab)
    <div class="import-desc-inherit-banner">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>Jesli puste - dziedziczy opisy z zakladki Domyslny</span>
        <div class="ml-auto flex items-center gap-2">
            <button type="button"
                    wire:click="copyDefaultToShop({{ $shopId }})"
                    class="import-desc-copy-btn">
                Wypelnij z domyslnego
            </button>
            @if($hasContent)
                <button type="button"
                        wire:click="clearShopDescriptions({{ $shopId }})"
                        wire:confirm="Wyczyscic opisy tego sklepu? Bedzie dziedziczyc z domyslnych."
                        class="import-desc-clear-btn">
                    Wyczysc opisy sklepu
                </button>
            @endif
        </div>
    </div>
@endif

{{-- Short description --}}
<div class="{{ $skipDescriptions ? 'opacity-50 pointer-events-none' : '' }}">
    <div class="flex items-center justify-between mb-2">
        <label class="text-sm font-medium text-gray-300">
            Krotki opis
            <span class="text-gray-500 font-normal ml-1">(summary)</span>
            @if($isShopTab)
                <span class="text-gray-600 font-normal ml-1">- {{ $shopName }}</span>
            @endif
        </label>
        <span class="text-xs {{ $shortLen > 500 ? 'text-amber-400' : 'text-gray-500' }}">
            {{ $shortLen }} / 500 znakow
        </span>
    </div>
    <textarea wire:model.blur="{{ $shortModel }}"
              rows="3"
              maxlength="1000"
              placeholder="{{ $isShopTab ? 'Wlasny krotki opis dla ' . ($shopName ?? 'sklepu') . ' (puste = dziedziczy domyslny)...' : 'Krotki opis produktu wyswietlany w listingu...' }}"
              class="form-textarea-dark w-full resize-none"></textarea>
    <p class="mt-1 text-xs text-gray-500">
        @if($isShopTab)
            Wlasny opis dla {{ $shopName }}. Puste pole = dziedziczy z zakladki Domyslny.
        @else
            Wyswietlany na listach produktow i w podsumowaniach
        @endif
    </p>
</div>

{{-- Long description --}}
<div class="{{ $skipDescriptions ? 'opacity-50 pointer-events-none' : '' }} mt-6">
    <div class="flex items-center justify-between mb-2">
        <label class="text-sm font-medium text-gray-300">
            Pelny opis
            <span class="text-gray-500 font-normal ml-1">(HTML)</span>
            @if($isShopTab)
                <span class="text-gray-600 font-normal ml-1">- {{ $shopName }}</span>
            @endif
        </label>
        <div class="flex items-center gap-3">
            <span class="text-xs {{ $longLen > 5000 ? 'text-amber-400' : 'text-gray-500' }}">
                {{ $longLen }} znakow
            </span>
            <button type="button"
                    wire:click="copyShortToLong"
                    class="text-xs text-blue-400 hover:text-blue-300 transition-colors">
                Kopiuj krotki opis
            </button>
        </div>
    </div>
    <textarea wire:model.blur="{{ $longModel }}"
              rows="8"
              placeholder="{{ $isShopTab ? 'Wlasny pelny opis dla ' . ($shopName ?? 'sklepu') . ' (puste = dziedziczy domyslny)...' : 'Pelny opis produktu z formatowaniem HTML...' }}"
              class="form-textarea-dark w-full resize-y font-mono text-sm"></textarea>
    <p class="mt-1 text-xs text-gray-500">
        @if($isShopTab)
            Wlasny opis dla {{ $shopName }}. Puste pole = dziedziczy z zakladki Domyslny. Obsluguje HTML.
        @else
            Pelny opis widoczny na stronie produktu. Obsluguje HTML.
        @endif
    </p>
</div>

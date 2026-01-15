{{-- ETAP_06 FAZA 5: Shop picker dropdown dla inline editing --}}
{{-- x-teleport to body to escape overflow/transform issues --}}
@php
    $pickerId = 'shop-picker-' . $product->id;
    $productId = $product->id;
@endphp

<template x-teleport="body">
    <div id="{{ $pickerId }}"
         class="shop-picker-dropdown"
         x-data="{
             visible: false,
             productId: {{ $productId }},
             init() {
                 // Use double RAF to ensure DOM is fully laid out after Livewire re-render
                 requestAnimationFrame(() => {
                     requestAnimationFrame(() => {
                         this.visible = true;
                         this.positionDropdown();
                     });
                 });
             },
             positionDropdown() {
                 const trigger = document.querySelector('tr[wire\\:key=\'pending-product-' + this.productId + '\'] .shop-picker-trigger');
                 if (!trigger) return;

                 const rect = trigger.getBoundingClientRect();
                 const dropdown = this.$el;
                 const dropdownHeight = dropdown.offsetHeight || 350;

                 let top = rect.bottom + 4;
                 if (top + dropdownHeight > window.innerHeight - 20) {
                     top = rect.top - dropdownHeight - 4;
                 }

                 let left = rect.left;
                 if (left + 300 > window.innerWidth - 20) {
                     left = window.innerWidth - 320;
                 }

                 dropdown.style.top = top + 'px';
                 dropdown.style.left = left + 'px';
             }
         }"
         x-init="init()"
         x-show="visible"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         @resize.window="positionDropdown()"
         @scroll.window="positionDropdown()"
         @click.outside="$wire.closeShopPicker()"
         @keydown.escape.window="$wire.closeShopPicker()"
         wire:key="shop-picker-{{ $product->id }}"
         wire:ignore.self>

    {{-- Header --}}
    <div class="px-4 py-3 border-b border-gray-700 flex-shrink-0">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-medium text-gray-200">Wybierz sklepy</h3>
            <button wire:click="closeShopPicker"
                    class="text-gray-400 hover:text-white p-1 rounded hover:bg-gray-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Shop tiles grid --}}
    <div class="p-4 flex-1 overflow-y-auto">
        @php
            $shops = $this->getAvailableShops();
        @endphp

        @if($shops->isNotEmpty())
            <div class="grid grid-cols-2 gap-2">
                @foreach($shops as $shop)
                    @php
                        $isSelected = in_array($shop->id, $selectedShopIds);
                    @endphp
                    <button wire:click="toggleShop({{ $shop->id }})"
                            type="button"
                            class="p-2.5 rounded-lg border-2 text-left transition-all
                                   {{ $isSelected
                                      ? 'border-purple-500 bg-purple-900/30 text-purple-200'
                                      : 'border-gray-600 bg-gray-700/30 text-gray-300 hover:border-gray-500 hover:bg-gray-700/50' }}">
                        <div class="flex items-center gap-2">
                            {{-- Checkbox indicator --}}
                            <div class="w-4 h-4 rounded border flex items-center justify-center flex-shrink-0
                                        {{ $isSelected
                                           ? 'bg-purple-500 border-purple-500'
                                           : 'border-gray-500' }}">
                                @if($isSelected)
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @endif
                            </div>

                            {{-- Shop info --}}
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-medium truncate">
                                    {{ $shop->name }}
                                </div>
                                @if($shop->url)
                                    <div class="text-[10px] text-gray-500 truncate">
                                        {{ parse_url($shop->url, PHP_URL_HOST) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </button>
                @endforeach
            </div>
        @else
            <div class="p-4 text-center text-gray-500 text-sm">
                Brak dostepnych sklepow
            </div>
        @endif
    </div>

    {{-- Footer z przyciskami --}}
    <div class="px-4 py-3 border-t border-gray-700 flex items-center justify-between bg-gray-800/50 flex-shrink-0">
        <span class="text-xs text-gray-500">
            @if(count($selectedShopIds) > 0)
                <svg class="w-3 h-3 inline text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Wybrano: {{ count($selectedShopIds) }}
            @else
                Wybierz sklepy
            @endif
        </span>
        <div class="flex items-center gap-2">
            <button wire:click="closeShopPicker"
                    class="px-3 py-1.5 text-xs text-gray-400 hover:text-white bg-gray-700 hover:bg-gray-600 rounded transition-colors">
                Anuluj
            </button>
            <button wire:click="saveShops"
                    class="px-3 py-1.5 text-xs text-white bg-purple-600 hover:bg-purple-500 rounded transition-colors
                           {{ count($selectedShopIds) === 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                    @if(count($selectedShopIds) === 0) disabled @endif>
                Zapisz
            </button>
        </div>
    </div>
    </div>
</template>

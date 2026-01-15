{{-- ETAP_06: Inline Shop Select - kompaktowy multi-select dropdown --}}
{{-- @props: $product --}}
@php
    $shopIds = $product->shop_ids ?? [];
    $shopCount = is_array($shopIds) ? count($shopIds) : 0;
    $shops = \App\Models\PrestaShopShop::where('is_active', true)->orderBy('name')->get();
    $selectedShops = $shops->whereIn('id', $shopIds);
    $firstShopName = $selectedShops->first()?->name ?? '';
@endphp

<div class="inline-shop-select"
     x-data="{
         open: false,
         selectedIds: @js($shopIds),
         shopCount: {{ $shopCount }},
         productId: {{ $product->id }}
     }">

    {{-- Trigger button --}}
    <button type="button"
            @click="open = !open"
            @click.outside="open = false"
            class="inline-shop-trigger group flex items-center gap-1 px-2 py-1 rounded text-xs transition-all cursor-pointer
                   {{ $shopCount > 0
                      ? 'bg-amber-900/40 text-amber-300 hover:bg-amber-900/60 border border-amber-500/30'
                      : 'bg-gray-700/50 text-gray-400 hover:bg-gray-600/50 border border-gray-600/50' }}">
        {{-- Lock icon --}}
        <svg class="w-3 h-3 flex-shrink-0 {{ $shopCount > 0 ? 'text-amber-400' : 'text-gray-500' }}"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>

        {{-- Label --}}
        <span class="truncate max-w-20">
            <template x-if="shopCount === 0">
                <span>Dodaj</span>
            </template>
            <template x-if="shopCount === 1">
                <span>{{ $firstShopName ?: '1 sklep' }}</span>
            </template>
            <template x-if="shopCount > 1">
                <span x-text="shopCount + ' sklepy'">{{ $shopCount }} sklepy</span>
            </template>
        </span>

        {{-- Chevron --}}
        <svg class="w-3 h-3 flex-shrink-0 transition-transform"
             :class="{ 'rotate-180': open }"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Dropdown menu --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="inline-shop-dropdown absolute z-50 mt-1 w-52 max-h-56 overflow-y-auto
                bg-gray-800 border border-gray-600 rounded-lg shadow-xl"
         style="display: none;">

        {{-- Header --}}
        <div class="px-3 py-2 border-b border-gray-700 flex items-center justify-between">
            <span class="text-xs text-gray-400">Wybierz sklepy</span>
            <span class="text-xs text-gray-500" x-text="'(' + shopCount + ')'"></span>
        </div>

        {{-- Shop options --}}
        @foreach($shops as $shop)
            <label class="flex items-center gap-2 px-3 py-2 cursor-pointer hover:bg-gray-700/50 transition-colors">
                <input type="checkbox"
                       value="{{ $shop->id }}"
                       :checked="selectedIds.includes({{ $shop->id }})"
                       @change="
                           if ($event.target.checked) {
                               selectedIds.push({{ $shop->id }});
                           } else {
                               selectedIds = selectedIds.filter(id => id !== {{ $shop->id }});
                           }
                           shopCount = selectedIds.length;
                           $wire.setShopsForProduct(productId, selectedIds);
                       "
                       class="form-checkbox-dark w-3.5 h-3.5">
                <span class="text-xs text-gray-300 truncate">{{ $shop->name }}</span>
            </label>
        @endforeach

        {{-- Footer with save/close --}}
        <div class="px-3 py-2 border-t border-gray-700 flex justify-end">
            <button type="button"
                    @click="open = false"
                    class="text-xs text-gray-400 hover:text-white px-2 py-1 rounded hover:bg-gray-700 transition-colors">
                Zamknij
            </button>
        </div>
    </div>
</div>

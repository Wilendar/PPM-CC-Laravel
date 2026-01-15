{{-- ETAP_06: Bulk Shop Dropdown dla naglowka tabeli --}}
@php
    $shops = \App\Models\PrestaShopShop::where('is_active', true)->orderBy('name')->get();
@endphp

<div class="bulk-shop-dropdown relative"
     x-data="{
         open: false,
         btnRect: null,
         updatePosition() {
             this.btnRect = this.$refs.btn.getBoundingClientRect();
         }
     }"
     @click.outside="open = false">

    <button type="button"
            x-ref="btn"
            @click="updatePosition(); open = !open"
            class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] rounded
                   bg-amber-900/40 text-amber-300 hover:bg-amber-900/60 border border-amber-500/30">
        <span>Ustaw</span>
        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <template x-teleport="body">
        <div x-show="open"
             x-transition
             @click.outside="open = false"
             class="fixed z-[9999] w-52 max-h-64 overflow-y-auto
                    bg-gray-800 border border-gray-600 rounded-lg shadow-xl"
             :style="btnRect ? `top: ${btnRect.bottom + 4}px; left: ${Math.max(0, btnRect.right - 208)}px;` : ''"
             style="display: none;">

            <div class="px-3 py-2 border-b border-gray-700 text-sm text-gray-400">
                Ustaw dla {{ count($selectedIds ?? []) }} produktow
            </div>

            @foreach($shops as $shop)
                <button type="button"
                        wire:click="bulkAddShop({{ $shop->id }})"
                        @click="open = false"
                        class="w-full px-3 py-2 text-left text-sm text-gray-300 hover:bg-gray-700/50 flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span class="truncate">{{ $shop->name }}</span>
                </button>
            @endforeach

            <div class="border-t border-gray-700 px-3 py-2">
                <button type="button"
                        wire:click="bulkClearShops"
                        @click="open = false"
                        class="w-full px-2 py-1.5 text-sm text-red-400 hover:bg-red-900/30 rounded flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Wyczysc sklepy
                </button>
            </div>
        </div>
    </template>
</div>

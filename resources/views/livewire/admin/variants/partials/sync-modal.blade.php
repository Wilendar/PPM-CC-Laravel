{{-- Sync Modal - PrestaShop sync status and actions --}}
@if($showSyncModal && $selectedValueIdForSync)
    @php
        $syncValue = \App\Models\AttributeValue::with('prestashopMappings.shop')->find($selectedValueIdForSync);
    @endphp
    @teleport('body')
    <div x-data="{ show: true }" x-show="show" x-cloak
         @keydown.escape.window="$wire.closeSyncModal()"
         class="fixed inset-0 z-50">

        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="$wire.closeSyncModal()"></div>

        <div class="relative z-10 h-full overflow-y-auto">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="relative bg-gray-800 rounded-lg shadow-xl max-w-xl w-full border border-gray-700" @click.stop>

                    {{-- Header --}}
                    <div class="px-6 py-4 border-b border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-white">Synchronizacja PrestaShop</h3>
                                @if($syncValue)
                                    <p class="text-sm text-gray-400 mt-1">
                                        Wartosc: <span class="font-semibold text-blue-400">{{ $syncValue->label }}</span>
                                    </p>
                                @endif
                            </div>
                            <button wire:click="closeSyncModal" class="text-gray-400 hover:text-white transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="px-6 py-4">
                        @if($syncValue)
                            {{-- Current Sync Status --}}
                            <div class="mb-6">
                                <h4 class="text-sm font-medium text-gray-400 mb-3">Aktualny status synchronizacji</h4>
                                @if($syncValue->prestashopMappings->count() > 0)
                                    <div class="space-y-2">
                                        @foreach($syncValue->prestashopMappings as $mapping)
                                            <div class="flex items-center justify-between p-3 bg-gray-900/50 rounded-lg border border-gray-700">
                                                <div class="flex items-center gap-3">
                                                    <span class="{{ $mapping->getStatusBadgeClass() }} inline-flex items-center px-2 py-1 text-xs font-medium rounded">
                                                        {{ $mapping->getStatusIcon() }} {{ $mapping->sync_status }}
                                                    </span>
                                                    <span class="text-gray-300">{{ $mapping->shop->name ?? 'Nieznany sklep' }}</span>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    PS ID: {{ $mapping->ps_attribute_id ?? 'brak' }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-4 text-gray-400">
                                        <span class="text-2xl">⚠️</span>
                                        <p class="mt-2">Brak mapowania do zadnego sklepu</p>
                                    </div>
                                @endif
                            </div>

                            {{-- Sync to Shop Actions --}}
                            <div class="border-t border-gray-700 pt-4">
                                <h4 class="text-sm font-medium text-gray-400 mb-3">Synchronizuj do sklepu</h4>
                                <div class="grid grid-cols-2 gap-3">
                                    @foreach($this->activeShops as $shop)
                                        @php
                                            $existingMapping = $syncValue->prestashopMappings->firstWhere('shop_id', $shop->id);
                                            $isSynced = $existingMapping && $existingMapping->sync_status === 'synced';
                                        @endphp
                                        <button
                                            wire:click="syncValueToShop({{ $syncValue->id }}, {{ $shop->id }})"
                                            wire:loading.attr="disabled"
                                            class="flex items-center justify-between p-3 rounded-lg border transition-colors
                                                {{ $isSynced
                                                    ? 'bg-green-500/10 border-green-500/30 text-green-400'
                                                    : 'bg-gray-900/50 border-gray-700 text-gray-300 hover:border-blue-500/50 hover:bg-blue-500/10' }}">
                                            <span>{{ $shop->name }}</span>
                                            @if($isSynced)
                                                <span class="text-sm">✓</span>
                                            @else
                                                <span class="text-sm">→</span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>

                                {{-- Loading indicator --}}
                                <div wire:loading wire:target="syncValueToShop" class="mt-4 text-center text-blue-400">
                                    <span class="animate-spin inline-block mr-2">⏳</span>
                                    Synchronizacja w toku...
                                </div>
                            </div>

                            {{-- Errors --}}
                            @error('sync')
                                <div class="mt-4 p-3 bg-red-500/10 border border-red-500/30 rounded-lg text-red-400 text-sm">
                                    {{ $message }}
                                </div>
                            @enderror
                        @endif
                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-4 border-t border-gray-700 flex justify-end">
                        <button wire:click="closeSyncModal" class="btn-enterprise-secondary">Zamknij</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endteleport
@endif

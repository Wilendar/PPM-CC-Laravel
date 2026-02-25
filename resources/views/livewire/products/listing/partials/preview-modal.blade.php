{{-- QUICK PREVIEW MODAL --}}
@if($showPreviewModal && $selectedProduct)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-4 text-center sm:p-0">
            {{-- Background overlay --}}
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" wire:click="closePreviewModal"></div>

            {{-- Modal content --}}
            <div class="inline-block align-middle bg-card rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                {{-- Header --}}
                <div class="bg-card px-6 py-4 border-b border-primary">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <h3 class="text-lg font-medium text-primary">
                                Podgląd produktu: {{ $selectedProduct->sku }}
                            </h3>
                            @if($selectedProduct->is_active)
                                <span class="ml-3 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-800 text-green-200">
                                    Aktywny
                                </span>
                            @else
                                <span class="ml-3 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-800 text-red-200">
                                    Nieaktywny
                                </span>
                            @endif
                        </div>
                        <button wire:click="closePreviewModal"
                                class="text-muted hover:text-primary transition-colors duration-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Content --}}
                <div class="bg-card px-6 py-4">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {{-- Product Info --}}
                        <div>
                            <h4 class="text-sm font-medium text-muted uppercase tracking-wider mb-3">Informacje podstawowe</h4>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-300">Nazwa</label>
                                    <p class="text-sm text-primary">{{ $selectedProduct->name }}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300">SKU</label>
                                        <p class="text-sm text-primary font-mono">{{ $selectedProduct->sku }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300">Kod dostawcy</label>
                                        <p class="text-sm text-primary">{{ $selectedProduct->supplier_code ?? '-' }}</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300">Producent</label>
                                        <p class="text-sm text-primary">{{ $selectedProduct->manufacturer ?? '-' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300">Typ produktu</label>
                                        <p class="text-sm text-primary">{{ $selectedProduct->productType->name ?? '-' }}</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Descriptions --}}
                            @if($selectedProduct->short_description)
                                <div class="mt-6">
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Krótki opis</label>
                                    <div class="text-sm text-muted bg-card-hover rounded-lg p-3">
                                        {{ Str::limit($selectedProduct->short_description, 200) }}
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Multi-Store Sync Status --}}
                        <div>
                            <h4 class="text-sm font-medium text-muted uppercase tracking-wider mb-3">Status synchronizacji</h4>
                            @php
                                $syncSummary = $selectedProduct->getMultiStoreSyncSummary();
                                $conflicts = $selectedProduct->getShopsWithConflicts();
                            @endphp

                            <div class="space-y-4">
                                {{-- Overall Sync Health --}}
                                <div class="bg-card-hover rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-300">Ogólny stan synchronizacji</span>
                                        <span class="text-lg font-bold text-primary">{{ $syncSummary['sync_health_percentage'] }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-700 rounded-full h-2">
                                        <div class="h-2 rounded-full {{ $syncSummary['sync_health_percentage'] >= 90 ? 'bg-green-500' : ($syncSummary['sync_health_percentage'] >= 70 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                             style="width: {{ $syncSummary['sync_health_percentage'] }}%"></div>
                                    </div>
                                </div>

                                {{-- Sync Stats --}}
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="bg-card-hover rounded-lg p-3 text-center">
                                        <div class="text-lg font-bold text-green-400">{{ $syncSummary['synced_shops'] }}</div>
                                        <div class="text-xs text-muted">Zsynchronizowane</div>
                                    </div>
                                    <div class="bg-card-hover rounded-lg p-3 text-center">
                                        <div class="text-lg font-bold text-blue-400">{{ $syncSummary['published_shops'] }}</div>
                                        <div class="text-xs text-muted">Opublikowane</div>
                                    </div>
                                    <div class="bg-card-hover rounded-lg p-3 text-center">
                                        <div class="text-lg font-bold text-orange-400">{{ $syncSummary['conflict_shops'] }}</div>
                                        <div class="text-xs text-muted">Konflikty</div>
                                    </div>
                                    <div class="bg-card-hover rounded-lg p-3 text-center">
                                        <div class="text-lg font-bold text-red-400">{{ $syncSummary['error_shops'] }}</div>
                                        <div class="text-xs text-muted">Błędy</div>
                                    </div>
                                </div>

                                {{-- Conflicts Details --}}
                                @if($conflicts->count() > 0)
                                    <div class="bg-orange-900/20 border border-orange-800 rounded-lg p-3">
                                        <h5 class="text-sm font-medium text-orange-300 mb-2">Konflikty wymagające uwagi:</h5>
                                        <div class="space-y-2">
                                            @foreach($conflicts->take(3) as $conflict)
                                                <div class="text-xs text-orange-200">
                                                    • {{ $conflict['shop_name'] }} - {{ $conflict['time_since_conflict'] }}
                                                </div>
                                            @endforeach
                                            @if($conflicts->count() > 3)
                                                <div class="text-xs text-orange-400">
                                                    ... i {{ $conflicts->count() - 3 }} więcej
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Actions Footer --}}
                <div class="bg-card px-6 py-4 border-t border-primary">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2 text-xs text-muted">
                            <span>Utworzono: {{ $selectedProduct->created_at->format('d.m.Y H:i') }}</span>
                            <span>&bull;</span>
                            <span>Aktualizacja: {{ $selectedProduct->updated_at->format('d.m.Y H:i') }}</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button wire:click="syncProduct({{ $selectedProduct->id }})"
                                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-purple-600 hover:bg-purple-700 transition-colors duration-300">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Synchronizuj
                            </button>
                            <a href="{{ route('products.edit', $selectedProduct) }}"
                               class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-orange-600 hover:bg-orange-700 transition-colors duration-300">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Edytuj
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

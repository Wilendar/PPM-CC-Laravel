{{-- CategoryConflictModal Component - Per-Shop Categories Conflict Resolution --}}
{{-- Enterprise conflict resolution system for category mismatches --}}
<div x-data="{ isOpen: @entangle('isOpen') }"
     x-show="isOpen"
     x-cloak
     class="fixed inset-0 overflow-y-auto z-[9999]"
     aria-labelledby="modal-title"
     role="dialog"
     aria-modal="true">

    <!-- Background Overlay -->
    <div x-show="isOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="isOpen = false"
         class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity"></div>

    <!-- Modal Container -->
    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0 relative modal-z-content">
        <div x-show="isOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             @click.stop
             class="relative transform overflow-hidden rounded-xl shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-5xl modal-bg-enterprise modal-border-brand">

            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-brand-500/30 bg-gradient-to-r from-gray-800 via-gray-900 to-gray-800">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white flex items-center gap-2" id="modal-title">
                            <svg class="w-5 h-5 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            Konflikt Struktury Kategorii
                        </h3>
                        <p class="text-sm text-gray-300 mt-1">
                            Produkt: <strong class="text-brand-400">{{ $productName }}</strong> | Sklep: <strong class="text-orange-400">{{ $shopName }}</strong>
                        </p>
                        @if($detectedAt)
                            <p class="text-xs text-gray-400 mt-0.5">
                                Wykryto: {{ \Carbon\Carbon::parse($detectedAt)->format('Y-m-d H:i:s') }}
                            </p>
                        @endif
                    </div>
                    <button @click="isOpen = false"
                            class="rounded-lg p-2 hover:bg-gray-700/50 transition-colors duration-200">
                        <svg class="w-6 h-6 text-gray-300 hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Info Bar -->
            <div class="px-6 py-4 bg-orange-900/20 border-b border-orange-500/30">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-orange-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="text-left">
                        <p class="text-sm text-orange-300 font-medium">
                            Wykryto różne kategorie podczas re-importu produktu
                        </p>
                        <p class="text-xs text-orange-200/80 mt-1">
                            Ten produkt został ponownie zaimportowany ze sklepu <strong>{{ $shopName }}</strong> z inną strukturą kategorii
                            niż kategorie domyślne. Wybierz, którą strukturę kategorii chcesz zachować.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Comparison View -->
            <div class="px-6 py-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Default Categories -->
                    <div class="bg-gray-800/50 border border-gray-600/50 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-3 pb-3 border-b border-gray-600/50">
                            <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                            </svg>
                            <h4 class="text-base font-bold text-white">Kategorie Domyślne</h4>
                            <span class="ml-auto text-xs text-gray-400 bg-gray-700 px-2 py-1 rounded-full">
                                {{ count($defaultCategories) }} {{ count($defaultCategories) === 1 ? 'kategoria' : 'kategorie' }}
                            </span>
                        </div>

                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            @forelse($defaultCategories as $category)
                                <div class="flex items-start gap-2 p-2 bg-gray-700/30 rounded-lg hover:bg-gray-700/50 transition-colors">
                                    <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-white truncate">{{ $category['name'] }}</p>
                                        <p class="text-xs text-gray-400 truncate">{{ $category['full_path'] }}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-400 italic py-4 text-center">Brak kategorii domyślnych</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Shop Categories -->
                    <div class="bg-gray-800/50 border border-orange-500/50 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-3 pb-3 border-b border-orange-500/50">
                            <svg class="w-5 h-5 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path>
                            </svg>
                            <h4 class="text-base font-bold text-white">Kategorie z Importu ({{ $shopName }})</h4>
                            <span class="ml-auto text-xs text-orange-300 bg-orange-900/50 px-2 py-1 rounded-full">
                                {{ count($shopCategories) }} {{ count($shopCategories) === 1 ? 'kategoria' : 'kategorie' }}
                            </span>
                        </div>

                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            @forelse($shopCategories as $category)
                                <div class="flex items-start gap-2 p-2 bg-orange-900/10 border border-orange-500/20 rounded-lg hover:bg-orange-900/20 transition-colors">
                                    <svg class="w-4 h-4 text-orange-500 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-white truncate">{{ $category['name'] }}</p>
                                        <p class="text-xs text-gray-400 truncate">{{ $category['full_path'] }}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-400 italic py-4 text-center">Brak kategorii ze sklepu</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Decision Help -->
                <div class="mt-6 p-4 bg-blue-900/20 border border-blue-500/30 rounded-lg">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="text-left">
                            <p class="text-sm font-medium text-blue-300 mb-1">Jak wybrać?</p>
                            <ul class="text-xs text-blue-200/80 space-y-1">
                                <li>• <strong class="text-blue-300">Kategorie Domyślne:</strong> Zachowaj spójną kategoryzację we wszystkich sklepach</li>
                                <li>• <strong class="text-orange-300">Kategorie z Importu:</strong> Użyj unikalnej struktury dla tego konkretnego sklepu</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-700/50 bg-gray-800/30 flex items-center justify-between flex-wrap gap-3">
                <button wire:click="close"
                        wire:loading.attr="disabled"
                        class="px-6 py-2 rounded-lg font-semibold text-sm text-white bg-gray-700 hover:bg-gray-600 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                    Anuluj (Nie rozwiązuj)
                </button>

                <div class="flex items-center gap-3">
                    <button wire:click="useDefaultCategories"
                            wire:loading.attr="disabled"
                            wire:target="useDefaultCategories"
                            class="px-6 py-2 rounded-lg font-semibold text-sm text-white bg-green-600 hover:bg-green-700 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" wire:loading.remove wire:target="useDefaultCategories">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" wire:loading wire:target="useDefaultCategories">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Użyj Domyślnych</span>
                    </button>

                    <button wire:click="useShopCategories"
                            wire:loading.attr="disabled"
                            wire:target="useShopCategories"
                            class="px-6 py-2 rounded-lg font-semibold text-sm text-white bg-orange-600 hover:bg-orange-700 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" wire:loading.remove wire:target="useShopCategories">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"></path>
                        </svg>
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" wire:loading wire:target="useShopCategories">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Użyj z Importu ({{ $shopName }})</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
